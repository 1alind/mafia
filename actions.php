<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/lang/loader.php';

function get_db() {
    $db = load_db();
    if (!$db || !is_array($db)) {
        $db = [
            'reset_token' => uniqid('rst_', true),
            'host_browser_id' => null,
            'host_password' => '1234',
            'phase' => 'setup',
            'day' => 1,
            'players' => [],
            'roles_shared' => false,
            'winner' => null,
            'night_actions' => [],
            'logs' => ['Game session created.'],
            'grave_keeper_charges' => 2,
            'grave_keeper_revealed_roles' => false,
            'grave_keeper_reveal_pending' => false,
            'grave_keeper_acted_tonight' => false,
            'gravedigger_charges' => 2,
            'gravedigger_reveal_pending' => false,
            'town_doctor_self_protect_count' => 0,
            'last_night_report' => null,
            'investigation_results' => [],
            'delayed_departure' => []
        ];
        save_db($db);
    }
    if (empty($db['host_password'])) {
        $db['host_password'] = '1234';
        save_db($db);
    }
    return $db;
}

$db = get_db();
$my_browser_id = $_COOKIE['mafia_browser_id'] ?? '';
$needs_host_claim = empty($db['host_browser_id']);
$is_host = (!empty($db['host_browser_id']) && $db['host_browser_id'] === $my_browser_id);

// Handle AJAX state request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['ajax']) || (isset($_GET['action']) && $_GET['action'] === 'get_state'))) {
    header('Content-Type: application/json');
    echo json_encode($db);
    exit;
}

function evaluate_investigation($target_name, $db) {
    $target_role = null;
    foreach ($db['players'] as $p) {
        if (trim($p['name']) === trim($target_name)) {
            $target_role = trim($p['role'] ?? '');
            break;
        }
    }

    if (!$target_role) return 'Citizen';

    // 1. If target’s role is mafia boss then it will show citizen.
    if ($target_role === 'Mafia Boss') {
        return 'Citizen';
    }

    // 2. Check if deceiver is alive and has targeted the investigator's target
    $deceiver_target = $db['night_actions']['Deceiver'] ?? null;
    $deceiver_alive = false;
    foreach ($db['players'] as $p) {
        if (($p['role'] ?? '') === 'Deceiver' && $p['status'] === 'alive') {
            $deceiver_alive = true;
            break;
        }
    }

    $is_deceived = ($deceiver_alive && $deceiver_target && trim($deceiver_target) === trim($target_name));

    // 3. Evaluate alignment
    $is_mafia_aligned = in_array($target_role, ['Mafia Doctor', 'Deceiver', 'Regular Mafia']);

    if ($is_deceived) {
        // opposite: if mafia -> citizen, if citizen -> mafia (Regular Mafia)
        return $is_mafia_aligned ? 'Citizen' : 'Regular Mafia';
    } else {
        // actual: if mafia -> mafia (Regular Mafia), if citizen -> citizen
        return $is_mafia_aligned ? 'Regular Mafia' : 'Citizen';
    }
}

function check_win_conditions(&$db) {
    if (!empty($db['winner'])) return;

    $alive_mafia = 0;
    $alive_citizens = 0;

    foreach ($db['players'] as $p) {
        if ($p['status'] === 'alive' || in_array($p['name'], $db['delayed_departure'] ?? [])) {
            $role = $p['role'] ?? '';
            if (in_array($role, ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'])) {
                $alive_mafia++;
            } else {
                $alive_citizens++;
            }
        }
    }

    if ($alive_mafia === 0 && count($db['players']) > 0 && $db['roles_shared']) {
        $db['winner'] = 'Citizens';
        $db['logs'][] = '🏆 GAME OVER: Citizens have won!';
    } elseif ($alive_mafia >= $alive_citizens && $alive_mafia > 0 && $db['roles_shared']) {
        $db['winner'] = 'Mafia';
        $db['logs'][] = '🏆 GAME OVER: Mafia have won!';
    }
}

function terminate_request($target) {
    global $db;
    if (isset($_POST['ajax']) || isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
        header('Content-Type: application/json');
        echo json_encode($db);
        exit;
    }
    header("Location: " . $target);
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'claim_host') {
        $input_pass = trim($_POST['host_password'] ?? '');
        $required_pass = $db['host_password'] ?? '1234';
        
        if ($input_pass === $required_pass) {
            $db['host_browser_id'] = $my_browser_id;
            $db['logs'][] = "Host claimed by browser {$my_browser_id}.";
            unset($_SESSION['host_error']);
            save_db($db);
        } else {
            $_SESSION['host_error'] = __('incorrect_host_password');
        }
        terminate_request("index.php");
    }

    if ($action === 'join_game') {
        if (empty($db['host_browser_id'])) {
            $_SESSION['join_error'] = __('no_host_error_desc');
            terminate_request("player.php");
        }

        $name = trim($_POST['player_name'] ?? '');
        if ($name !== '') {
            $exists = false;
            foreach ($db['players'] as &$p) {
                if (strcasecmp($p['name'], $name) === 0) {
                    $_SESSION['player_id'] = $p['id'];
                    $p['browser_id'] = $my_browser_id;
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $pid = 'p_' . substr(bin2hex(random_bytes(4)), 0, 8);
                $db['players'][] = [
                    'id' => $pid,
                    'name' => $name,
                    'browser_id' => $my_browser_id,
                    'role' => 'Pending',
                    'status' => 'alive'
                ];
                $_SESSION['player_id'] = $pid;
                $db['logs'][] = "Player '{$name}' joined the game.";
            }
            save_db($db);
        }
        terminate_request("player.php");
    }

    if ($is_host) {
        if ($action === 'share_roles') {
            $mafia_count = max(1, min(15, (int)($_POST['mafia_count'] ?? 2)));
            $players = &$db['players'];
            $total_players = count($players);

            if ($total_players > 0) {
                shuffle($players);

                $deck = [];
                $deck[] = 'Mafia Boss';
                
                $remaining_mafia_slots = $mafia_count - 1;
                $special_roles_input = $_POST['special_roles'] ?? [];
                
                $mafia_specials = array_values(array_intersect($special_roles_input, ['Mafia Doctor', 'Deceiver']));
                shuffle($mafia_specials);
                
                for ($i = 0; $i < $remaining_mafia_slots; $i++) {
                    if (!empty($mafia_specials)) {
                        $deck[] = array_shift($mafia_specials);
                    } else {
                        $deck[] = 'Regular Mafia';
                    }
                }

                $citizen_specials = array_values(array_intersect($special_roles_input, ['Police', 'Town Doctor', 'Investigator', 'Grave Keeper', 'Judge', 'Mirhas', 'Suicidal Bomb']));
                shuffle($citizen_specials);

                while (count($deck) < $total_players && !empty($citizen_specials)) {
                    $deck[] = array_shift($citizen_specials);
                }

                while (count($deck) < $total_players) {
                    $deck[] = 'Citizen';
                }

                shuffle($deck);

                for ($i = 0; $i < $total_players; $i++) {
                    $players[$i]['role'] = $deck[$i];
                    $players[$i]['status'] = 'alive';
                }

                $db['roles_shared'] = true;
                $db['phase'] = 'night';
                $db['day'] = 1;
                $db['night_actions'] = [];
                $db['winner'] = null;
                $db['reset_token'] = uniqid('rst_', true);
                $db['logs'][] = "Roles distributed to {$total_players} players ({$mafia_count} Mafia). Night 1 started.";
                save_db($db);
            }
            terminate_request("index.php");
        }

        if ($action === 'hide_roles') {
            foreach ($db['players'] as &$p) {
                $p['role'] = 'Pending';
                $p['status'] = 'alive';
            }
            $db['roles_shared'] = false;
            $db['phase'] = 'setup';
            $db['day'] = 1;
            $db['winner'] = null;
            $db['night_actions'] = [];
            $db['last_night_report'] = null;
            $db['investigation_results'] = [];
            $db['delayed_departure'] = [];
            $db['grave_keeper_charges'] = 2;
            $db['grave_keeper_revealed_roles'] = false;
            $db['grave_keeper_reveal_pending'] = false;
            $db['grave_keeper_acted_tonight'] = false;
            $db['gravedigger_charges'] = 2;
            $db['gravedigger_reveal_pending'] = false;
            $db['town_doctor_self_protect_count'] = 0;
            unset($db['suicidal_bomb_triggered_by']);
            $db['reset_token'] = uniqid('rst_', true);
            $db['logs'][] = "Game reset for a Rematch.";
            save_db($db);
            terminate_request("index.php");
        }

        if ($action === 'add_bot' || $action === 'add_five_bots') {
            $num_bots = ($action === 'add_five_bots') ? 5 : 1;
            $bot_names_pool = ["Azad", "Berivan", "Shergo", "Dilshad", "Ronahi", "Karwan", "Hevi", "Jiyan", "Sidar", "Kawa", "Chinar", "Dara", "Zozan", "Soran", "Nazan", "Avrin", "Alan", "Rojhat", "Zinar", "Bahar", "Shvan"];
            
            $existing_names = [];
            foreach ($db['players'] as $p) {
                $existing_names[] = strtolower($p['name']);
            }
            
            for ($i = 0; $i < $num_bots; $i++) {
                $found_name = null;
                foreach ($bot_names_pool as $candidate) {
                    $candidate_with_bot = "Bot " . $candidate;
                    if (!in_array(strtolower($candidate_with_bot), $existing_names)) {
                        $found_name = $candidate_with_bot;
                        break;
                    }
                }
                
                if (!$found_name) {
                    $counter = 1;
                    while (true) {
                        $candidate_with_bot = "Bot " . $counter;
                        if (!in_array(strtolower($candidate_with_bot), $existing_names)) {
                            $found_name = $candidate_with_bot;
                            break;
                        }
                        $counter++;
                    }
                }
                
                $existing_names[] = strtolower($found_name);
                
                $pid = 'p_' . substr(bin2hex(random_bytes(4)), 0, 8);
                $db['players'][] = [
                    'id' => $pid,
                    'name' => $found_name,
                    'browser_id' => 'bot_' . $pid,
                    'role' => 'Pending',
                    'status' => 'alive'
                ];
                $db['logs'][] = "Bot player '{$found_name}' joined the game.";
            }
            save_db($db);
            terminate_request("index.php");
        }

        if ($action === 'remove_player_setup') {
            $pid = $_POST['player_id'] ?? '';
            foreach ($db['players'] as $key => $p) {
                if ($p['id'] === $pid) {
                    $db['logs'][] = "Player '{$p['name']}' was removed from the lobby.";
                    unset($db['players'][$key]);
                    break;
                }
            }
            $db['players'] = array_values($db['players']);
            save_db($db);
            terminate_request("index.php");
        }

        if ($action === 'hard_reset') {
            $existing_pass = $db['host_password'] ?? '1234';
            $db = [
                'reset_token' => uniqid('rst_', true),
                'host_browser_id' => null,
                'host_password' => $existing_pass,
                'phase' => 'setup',
                'day' => 1,
                'players' => [],
                'roles_shared' => false,
                'winner' => null,
                'night_actions' => [],
                'logs' => ['Hard reset executed. Session cleared.'],
                'grave_keeper_charges' => 2,
                'grave_keeper_revealed_roles' => false,
                'grave_keeper_reveal_pending' => false,
                'grave_keeper_acted_tonight' => false,
                'gravedigger_charges' => 2,
                'gravedigger_reveal_pending' => false,
                'town_doctor_self_protect_count' => 0,
                'last_night_report' => null,
                'investigation_results' => [],
                'delayed_departure' => []
            ];
            save_db($db);
            terminate_request("index.php");
        }

        if ($action === 'record_night_target') {
            $role = $_POST['role'] ?? '';
            $target_id = $_POST['target_id'] ?? $_POST['reveal_answer'] ?? '';

            if ($role === 'Grave Keeper') {
                $answer = $target_id;
                $is_gk_dead = false;
                foreach ($db['players'] as $p) {
                    if (($p['role'] ?? '') === 'Grave Keeper') {
                        if ($p['status'] === 'dead' || in_array($p['name'], $db['delayed_departure'] ?? [])) {
                            $is_gk_dead = true;
                        }
                        break;
                    }
                }

                if ($is_gk_dead) {
                    $answer = 'no';
                }

                if ($answer === 'yes') {
                    $db['grave_keeper_reveal_pending'] = true;
                    if (($db['grave_keeper_charges'] ?? 2) > 0) {
                        $db['grave_keeper_charges'] = ($db['grave_keeper_charges'] ?? 2) - 1;
                        $db['gravedigger_charges'] = $db['grave_keeper_charges'];
                    }
                } else {
                    $db['grave_keeper_reveal_pending'] = false;
                }
                $db['grave_keeper_revealed_roles'] = false;
                $db['grave_keeper_acted_tonight'] = true;
                $db['night_actions']['Grave Keeper'] = $answer;
            } else {
                $target_name = null;

                if ($target_id === 'none') {
                    $db['night_actions'][$role] = 'none';
                } else {
                    if ($target_id !== '') {
                        foreach ($db['players'] as $p) {
                            if ($p['id'] === $target_id) {
                                $target_name = $p['name'];
                                break;
                            }
                        }
                    }

                    if ($target_name) {
                        $db['night_actions'][$role] = $target_name;
                    } else {
                        unset($db['night_actions'][$role]);
                    }
                }
            }

            // Always recalculate investigation result if Investigator target is selected
            $investigator_target = $db['night_actions']['Investigator'] ?? null;
            if ($investigator_target && $investigator_target !== 'none') {
                $eval_res = evaluate_investigation($investigator_target, $db);
                $db['investigation_results'] = [
                    ['target' => $investigator_target, 'result' => $eval_res]
                ];
            } else {
                $db['investigation_results'] = [];
            }

            save_db($db);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode($db);
                exit;
            }
            terminate_request("index.php");
        }

        if ($action === 'submit_all_night_actions') {
            $actions = json_decode($_POST['actions'], true);
            foreach ($actions as $role => $target_id) {
                if ($role === 'Grave Keeper') {
                    $answer = $target_id;
                    $is_gk_dead = false;
                    foreach ($db['players'] as $p) {
                        if (($p['role'] ?? '') === 'Grave Keeper') {
                            if ($p['status'] === 'dead' || in_array($p['name'], $db['delayed_departure'] ?? [])) {
                                $is_gk_dead = true;
                            }
                            break;
                        }
                    }

                    if ($is_gk_dead) {
                        $answer = 'no';
                    }

                    if ($answer === 'yes') {
                        $db['grave_keeper_reveal_pending'] = true;
                        if (($db['grave_keeper_charges'] ?? 2) > 0) {
                            $db['grave_keeper_charges'] = ($db['grave_keeper_charges'] ?? 2) - 1;
                            $db['gravedigger_charges'] = $db['grave_keeper_charges'];
                        }
                    } else {
                        $db['grave_keeper_reveal_pending'] = false;
                    }
                    $db['grave_keeper_revealed_roles'] = false;
                    $db['grave_keeper_acted_tonight'] = true;
                    $db['night_actions']['Grave Keeper'] = $answer;
                    continue;
                }

                $target_name = null;
                if ($target_id === 'none') {
                    $db['night_actions'][$role] = 'none';
                } else {
                    if ($target_id !== '') {
                        foreach ($db['players'] as $p) {
                            if ($p['id'] === $target_id || $p['name'] === $target_id) {
                                $target_name = $p['name'];
                                break;
                            }
                        }
                    }
                    if ($target_name) {
                        $db['night_actions'][$role] = $target_name;
                    } else {
                        unset($db['night_actions'][$role]);
                    }
                }
            }
            
            // Always recalculate investigation result if Investigator target is selected
            $investigator_target = $db['night_actions']['Investigator'] ?? null;
            if ($investigator_target && $investigator_target !== 'none') {
                $eval_res = evaluate_investigation($investigator_target, $db);
                $db['investigation_results'] = [
                    ['target' => $investigator_target, 'result' => $eval_res]
                ];
            } else {
                $db['investigation_results'] = [];
            }
            
            save_db($db);
            header('Content-Type: application/json');
            echo json_encode($db);
            exit;
        }

        if ($action === 'answer_grave_keeper_reveal') {
            $answer = $_POST['reveal_answer'] ?? 'no';

            // Check if Grave Keeper is dead
            $is_gk_dead = false;
            foreach ($db['players'] as $p) {
                if (($p['role'] ?? '') === 'Grave Keeper') {
                    if ($p['status'] === 'dead' || in_array($p['name'], $db['delayed_departure'] ?? [])) {
                        $is_gk_dead = true;
                    }
                    break;
                }
            }

            if ($is_gk_dead) {
                $answer = 'no';
            }

            if ($answer === 'yes') {
                $db['grave_keeper_reveal_pending'] = true;
                if (($db['grave_keeper_charges'] ?? 2) > 0) {
                    $db['grave_keeper_charges'] = ($db['grave_keeper_charges'] ?? 2) - 1;
                    $db['gravedigger_charges'] = $db['grave_keeper_charges'];
                }
            } else {
                $db['grave_keeper_reveal_pending'] = false;
            }
            $db['grave_keeper_revealed_roles'] = false;
            $db['grave_keeper_acted_tonight'] = true;
            save_db($db);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode($db);
                exit;
            }
            terminate_request("index.php");
        }

        if ($action === 'next_phase') {
            if ($db['phase'] === 'night') {
                $mafia_target = $db['night_actions']['Mafia'] ?? $db['night_actions']['Mafia Boss'] ?? null;
                $mafia_doc_target = $db['night_actions']['Mafia Doctor'] ?? null;
                $town_doc_target = $db['night_actions']['Town Doctor'] ?? null;
                $police_target = $db['night_actions']['Police'] ?? null;
                $suicidal_bomb_target = $db['night_actions']['Suicidal Bomb'] ?? null;

                if ($mafia_target === 'none') $mafia_target = null;
                if ($mafia_doc_target === 'none') $mafia_doc_target = null;
                if ($town_doc_target === 'none') $town_doc_target = null;
                if ($police_target === 'none') $police_target = null;
                if ($suicidal_bomb_target === 'none') $suicidal_bomb_target = null;

                // Validate alive status of role holders
                if ($town_doc_target) {
                    $town_doc_alive = false;
                    foreach ($db['players'] as $p) {
                        if (($p['role'] ?? '') === 'Town Doctor' && $p['status'] === 'alive' && !in_array($p['name'], $db['delayed_departure'] ?? [])) {
                            $town_doc_alive = true;
                            break;
                        }
                    }
                    if (!$town_doc_alive) $town_doc_target = null;
                }

                if ($mafia_doc_target) {
                    $mafia_doc_alive = false;
                    foreach ($db['players'] as $p) {
                        if (($p['role'] ?? '') === 'Mafia Doctor' && $p['status'] === 'alive' && !in_array($p['name'], $db['delayed_departure'] ?? [])) {
                            $mafia_doc_alive = true;
                            break;
                        }
                    }
                    if (!$mafia_doc_alive) $mafia_doc_target = null;
                }

                if ($mafia_target) {
                    $mafia_alive = false;
                    foreach ($db['players'] as $p) {
                        if (in_array($p['role'] ?? '', ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia']) && $p['status'] === 'alive' && !in_array($p['name'], $db['delayed_departure'] ?? [])) {
                            $mafia_alive = true;
                            break;
                        }
                    }
                    if (!$mafia_alive) $mafia_target = null;
                }

                if ($police_target) {
                    $police_player_name = null;
                    foreach ($db['players'] as $p) {
                        if (($p['role'] ?? '') === 'Police' && $p['status'] === 'alive' && !in_array($p['name'], $db['delayed_departure'] ?? [])) {
                            $police_player_name = $p['name'];
                            break;
                        }
                    }
                    if (!$police_player_name) $police_target = null;
                }

                if ($suicidal_bomb_target) {
                    $bomb_player_name = null;
                    foreach ($db['players'] as $p) {
                        if (($p['role'] ?? '') === 'Suicidal Bomb' && $p['status'] === 'alive' && !in_array($p['name'], $db['delayed_departure'] ?? [])) {
                            $bomb_player_name = $p['name'];
                            break;
                        }
                    }
                    if (!$bomb_player_name) $suicidal_bomb_target = null;
                }

                if ($town_doc_target) {
                    foreach ($db['players'] as $p) {
                        if ($p['name'] === $town_doc_target && ($p['role'] ?? '') === 'Town Doctor') {
                            $db['town_doctor_self_protect_count'] = ($db['town_doctor_self_protect_count'] ?? 0) + 1;
                            break;
                        }
                    }
                }

                $killed_names = [];
                $saved_names = [];

                if ($mafia_target) {
                    if ($mafia_target === $town_doc_target || $mafia_target === $mafia_doc_target) {
                        $saved_names[] = $mafia_target;
                    } else {
                        $killed_names[] = $mafia_target;

                        // Check if Suicidal Bomb was shot by Mafia without Doctor protection
                        $mafia_target_role = null;
                        foreach ($db['players'] as $p) {
                            if ($p['name'] === $mafia_target) {
                                $mafia_target_role = $p['role'] ?? '';
                                break;
                            }
                        }

                        if ($mafia_target_role === 'Suicidal Bomb') {
                            // Find active Mafia shooter (Mafia Boss or active Mafia)
                            $mafia_shooter = null;
                            foreach ($db['players'] as $p) {
                                if (($p['role'] ?? '') === 'Mafia Boss' && $p['status'] === 'alive') {
                                    $mafia_shooter = $p['name'];
                                    break;
                                }
                            }
                            if (!$mafia_shooter) {
                                foreach ($db['players'] as $p) {
                                    if (in_array($p['role'] ?? '', ['Mafia Doctor', 'Deceiver', 'Regular Mafia']) && $p['status'] === 'alive') {
                                        $mafia_shooter = $p['name'];
                                        break;
                                    }
                                }
                            }
                            if ($mafia_shooter && !in_array($mafia_shooter, $killed_names)) {
                                $killed_names[] = $mafia_shooter;
                                $db['logs'][] = "💣 Suicidal Bomb ({$mafia_target}) was shot by Mafia! The bomb exploded, eliminating both the Suicidal Bomb and the Mafia shooter ({$mafia_shooter})!";
                            }
                        }
                    }
                }

                if ($police_target) {
                    $police_player_name = null;
                    foreach ($db['players'] as $p) {
                        if (($p['role'] ?? '') === 'Police' && $p['status'] === 'alive') {
                            $police_player_name = $p['name'];
                            break;
                        }
                    }

                    $police_target_role = null;
                    foreach ($db['players'] as $p) {
                        if ($p['name'] === $police_target) {
                            $police_target_role = $p['role'] ?? '';
                            break;
                        }
                    }

                    $is_mafia_target = in_array($police_target_role, ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia']);

                    // If Police targets an innocent Citizen, Police is kicked out and Citizen stays alive
                    if (!$is_mafia_target) {
                        if ($police_player_name && !in_array($police_player_name, $killed_names)) {
                            $killed_names[] = $police_player_name;
                            $db['logs'][] = "⚠️ Police ({$police_player_name}) targeted an innocent Citizen ({$police_target}) and was kicked out of the game! The Citizen survives.";
                        }
                    } else {
                        // Bullet shot on Mafia target
                        if ($police_target === $town_doc_target || $police_target === $mafia_doc_target) {
                            if (!in_array($police_target, $saved_names)) {
                                $saved_names[] = $police_target;
                            }
                        } else {
                            if (!in_array($police_target, $killed_names)) {
                                $killed_names[] = $police_target;
                            }
                        }
                    }
                }

                // Suicidal Bomb Active Night Detonation
                $bomb_player_name = null;
                if ($suicidal_bomb_target) {
                    foreach ($db['players'] as $p) {
                        if (($p['role'] ?? '') === 'Suicidal Bomb' && $p['status'] === 'alive') {
                            $bomb_player_name = $p['name'];
                            break;
                        }
                    }
                    if ($bomb_player_name) {
                        if (!in_array($bomb_player_name, $killed_names)) {
                            $killed_names[] = $bomb_player_name;
                        }
                        if (!in_array($suicidal_bomb_target, $killed_names)) {
                            $killed_names[] = $suicidal_bomb_target;
                        }
                        // Suicidal Bomb explosion bypasses doctor protection completely for both
                        $saved_names = array_values(array_diff($saved_names, [$bomb_player_name, $suicidal_bomb_target]));
                        $db['logs'][] = "💥 Suicidal Bomb ({$bomb_player_name}) detonated at night on {$suicidal_bomb_target}! Both were eliminated (bypassing doctor protection and roles).";
                    }
                }

                $final_killed = [];
                foreach ($killed_names as $kname) {
                    foreach ($db['players'] as &$p) {
                        if ($p['name'] === $kname) {
                            if (($p['role'] ?? '') === 'Mirhas') {
                                if (($bomb_player_name && $kname === $bomb_player_name) || ($suicidal_bomb_target && $kname === $suicidal_bomb_target)) {
                                    $p['status'] = 'dead';
                                    $final_killed[] = $kname;
                                } elseif (!in_array($kname, $db['delayed_departure'] ?? [])) {
                                    $db['delayed_departure'][] = $kname;
                                    $db['logs'][] = "Mirhas ({$kname}) was targeted but stays alive for 1 day.";
                                } else {
                                    $p['status'] = 'dead';
                                    $final_killed[] = $kname;
                                }
                            } else {
                                $p['status'] = 'dead';
                                $final_killed[] = $kname;
                            }
                            break;
                        }
                    }
                }

                if (!empty($db['delayed_departure'])) {
                    foreach ($db['delayed_departure'] as $d_idx => $dname) {
                        foreach ($db['players'] as &$p) {
                            if ($p['name'] === $dname) {
                                $p['status'] = 'dead';
                                if (!in_array($dname, $final_killed)) {
                                    $final_killed[] = $dname;
                                }
                                unset($db['delayed_departure'][$d_idx]);
                                break;
                            }
                        }
                    }
                }

                $diary = [];
                
                // 1. Mafia Target
                if ($mafia_target) {
                    $diary[] = [
                        'en' => "• 🔪 <strong>Mafia</strong> targeted <strong class='text-rose-400'>$mafia_target</strong>.",
                        'ku' => "• 🔪 <strong>مافیا</strong> تەقە ل <strong class='text-rose-400'>$mafia_target</strong> کر.",
                        'ar' => "• 🔪 <strong>المافيا</strong> استهدفت <strong class='text-rose-400'>$mafia_target</strong>."
                    ];
                } else {
                    $diary[] = [
                        'en' => "• 🔪 <strong>Mafia</strong> did not choose any target.",
                        'ku' => "• 🔪 <strong>مافیا</strong> چ کەس کەنەکرە ئارمانج.",
                        'ar' => "• 🔪 <strong>المافيا</strong> لم تختر أي هدف."
                    ];
                }

                // 2. Town Doctor Protection
                if ($town_doc_target) {
                    $diary[] = [
                        'en' => "• 🩺 <strong>Town Doctor</strong> protected <strong class='text-emerald-400'>$town_doc_target</strong>.",
                        'ku' => "• 🩺 <strong>نوژدارێ هاولاتی</strong> پاراستن ل <strong class='text-emerald-400'>$town_doc_target</strong> کر.",
                        'ar' => "• 🩺 <strong>طبيب البلدة</strong> قام بحماية <strong class='text-emerald-400'>$town_doc_target</strong>."
                    ];
                }

                // 3. Mafia Doctor Protection
                if ($mafia_doc_target) {
                    $diary[] = [
                        'en' => "• 🧪 <strong>Mafia Doctor</strong> protected <strong class='text-rose-400'>$mafia_doc_target</strong>.",
                        'ku' => "• 🧪 <strong>نوژدارێ مافیا</strong> پاراستن ل <strong class='text-rose-400'>$mafia_doc_target</strong> کر.",
                        'ar' => "• 🧪 <strong>طبيب المافيا</strong> قام بحماية <strong class='text-rose-400'>$mafia_doc_target</strong>."
                    ];
                }

                // 4. Police Target
                if ($police_target) {
                    $diary[] = [
                        'en' => "• 👮 <strong>Police</strong> targeted <strong class='text-sky-400'>$police_target</strong>.",
                        'ku' => "• 👮 <strong>پۆلیس</strong> تەقە ل <strong class='text-sky-400'>$police_target</strong> کر.",
                        'ar' => "• 👮 <strong>الشرطي</strong> استهدف <strong class='text-sky-400'>$police_target</strong>."
                    ];
                }

                // 5. Deceiver Target
                $deceiver_target = $db['night_actions']['Deceiver'] ?? null;
                if ($deceiver_target) {
                    $diary[] = [
                        'en' => "• 🎭 <strong>Deceiver</strong> disguised <strong class='text-violet-400'>$deceiver_target</strong>.",
                        'ku' => "• 🎭 <strong>فێلبازێ مافیا</strong> فێڵ ل سەر <strong class='text-violet-400'>$deceiver_target</strong> کر.",
                        'ar' => "• 🎭 <strong>مخادع المافيا</strong> قام بتمويه <strong class='text-violet-400'>$deceiver_target</strong>."
                    ];
                }

                // 6. Investigator Target
                $investigator_target = $db['night_actions']['Investigator'] ?? null;
                if ($investigator_target) {
                    $eval_res = evaluate_investigation($investigator_target, $db);
                    $target_role = '';
                    foreach($db['players'] as $p) { if ($p['name'] === $investigator_target) $target_role = $p['role'] ?? ''; }
                    
                    $diary[] = [
                        'en' => "• 🔍 <strong>Investigator</strong> checked <strong class='text-amber-400'>$investigator_target</strong> (Role: $target_role) and found them as: <strong class='text-white underline'>$eval_res</strong>.",
                        'ku' => "• 🔍 <strong>ڤەکولەر</strong> ل سەر <strong class='text-amber-400'>$investigator_target</strong> لێکۆڵینەوە کر و دیت کو ئەو یێ دیارە وەک: <strong class='text-white underline'>$eval_res</strong>.",
                        'ar' => "• 🔍 <strong>المحقق</strong> كشف على <strong class='text-amber-400'>$investigator_target</strong> وظهر له كـ: <strong class='text-white underline'>$eval_res</strong>."
                    ];
                }

                // 7. Suicidal Bomb Target
                if ($suicidal_bomb_target) {
                    $diary[] = [
                        'en' => "• 💣 <strong>Suicidal Bomb</strong> targeted <strong class='text-red-500'>$suicidal_bomb_target</strong> for night explosion.",
                        'ku' => "• 💣 <strong>بۆمبێ</strong> خۆ ل سەر <strong class='text-red-500'>$suicidal_bomb_target</strong> بەرهەڤکر بۆ تەقاندنێ.",
                        'ar' => "• 💣 <strong>الانتحاري</strong> استهدف <strong class='text-red-500'>$suicidal_bomb_target</strong> للتفجير الليلة."
                    ];
                }

                // 8. Resulting Checks (Doctors, Mirhas, and deaths)
                if ($mafia_target) {
                    $saved_by_doc = ($mafia_target === $town_doc_target || $mafia_target === $mafia_doc_target);
                    if ($suicidal_bomb_target && $mafia_target === $bomb_player_name) {
                        $saved_by_doc = false;
                    }
                    if ($saved_by_doc) {
                        $diary[] = [
                            'en' => "🛡️ <strong>Doctor Protection:</strong> <strong class='text-white'>$mafia_target</strong> was shot by the Mafia but was <span class='text-emerald-400 font-bold underline'>successfully saved</span> by a Doctor!",
                            'ku' => "🛡️ <strong>پاراستنا نوژداری:</strong> تەقە ل <strong class='text-white'>$mafia_target</strong> هاتە کرن ژ لایێ مافیایێ ڤە، بەس ژ لایێ نوژداری ڤە <span class='text-emerald-400 font-bold underline'>هاتە پاراستن</span>!",
                            'ar' => "🛡️ <strong>حماية الطبيب:</strong> تم إطلاق النار على <strong class='text-white'>$mafia_target</strong> من المافيا ولكن تم <span class='text-emerald-400 font-bold underline'>إنقاذه بنجاح</span> بواسطة الطبيب!"
                        ];
                    } else {
                        // Check if Mirhas
                        $is_mirhas = false;
                        foreach ($db['players'] as $p) {
                            if ($p['name'] === $mafia_target && ($p['role'] ?? '') === 'Mirhas') {
                                $is_mirhas = true;
                                break;
                            }
                        }
                        if ($is_mirhas) {
                            $diary[] = [
                                'en' => "🛡️ <strong>Mirhas Resistance:</strong> <strong class='text-white'>$mafia_target</strong> was shot but survives for 1 extra day due to Mirhas passive ability.",
                                'ku' => "🛡️ <strong>خۆڕاگرییا مێرخاسی:</strong> تەقە ل <strong class='text-white'>$mafia_target</strong> هاتە کرن بەس ژ بەر شیانێن وی ئەو دێ بۆ ماوێ ۱ رۆژێ د زیندێ دا مینیت.",
                                'ar' => "🛡️ <strong>مقاومة ميرخاس:</strong> تم إطلاق النار على <strong class='text-white'>$mafia_target</strong> ولكنه يبقى حياً ليوم واحد إضافي بسبب قدرته."
                            ];
                        } else {
                            $diary[] = [
                                'en' => "💀 <strong>Eliminated:</strong> <strong class='text-white'>$mafia_target</strong> was shot and died.",
                                'ku' => "💀 <strong>دەرکەفتن:</strong> تەقە ل <strong class='text-white'>$mafia_target</strong> هاتە کرن و مریت.",
                                'ar' => "💀 <strong>تصفية:</strong> تم إطلاق النار على <strong class='text-white'>$mafia_target</strong> وتوفي."
                            ];
                        }
                    }
                }

                if ($police_target) {
                    $is_mafia = in_array($police_target_role, ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia']);
                    if (!$is_mafia) {
                        $diary[] = [
                            'en' => "⚠️ <strong>Police Penalty:</strong> Police targeted innocent <strong class='text-white'>$police_target</strong>. Police player <strong class='text-white'>$police_player_name</strong> died as penalty.",
                            'ku' => "⚠️ <strong>سزایێ پۆلیسی:</strong> پۆلیسی تەقە ل وەلاتیێ بێ گونەهـ <strong class='text-white'>$police_target</strong> کر! پۆلیس خۆ ب خۆ <strong class='text-white'>$police_player_name</strong> مریت.",
                            'ar' => "⚠️ <strong>عقوبة الشرطي:</strong> استهدف الشرطي مواطناً بريئاً <strong class='text-white'>$police_target</strong>. توفي الشرطي <strong class='text-white'>$police_player_name</strong> كعقوبة."
                        ];
                    } else {
                        $saved_by_doc = ($police_target === $town_doc_target || $police_target === $mafia_doc_target);
                        if ($saved_by_doc) {
                            $diary[] = [
                                'en' => "🛡️ <strong>Doctor Protection:</strong> Mafia member <strong class='text-white'>$police_target</strong> was shot by Police but <span class='text-emerald-400 font-bold underline'>saved by a Doctor</span>!",
                                'ku' => "🛡️ <strong>پاراستنا نوژداری:</strong> ئەندامێ مافیایێ <strong class='text-white'>$police_target</strong> ژ لایێ پۆلیسی ڤە هاتە تەقکرن بەس نوژداری <span class='text-emerald-400 font-bold underline'>ئەو پاراست</span>!",
                                'ar' => "🛡️ <strong>حماية الطبيب:</strong> تم استهداف المافيا <strong class='text-white'>$police_target</strong> من الشرطي ولكن تم <span class='text-emerald-400 font-bold underline'>إنقاذه بواسطة الطبيب</span>!"
                            ];
                        } else {
                            $diary[] = [
                                'en' => "💀 <strong>Mafia Dead:</strong> Mafia member <strong class='text-white'>$police_target</strong> was shot by Police and died.",
                                'ku' => "💀 <strong>کوشتنا مافیایێ:</strong> ئەندامێ مافیایێ <strong class='text-white'>$police_target</strong> ژ لایێ پۆلیسی ڤە هاتە کوشتن و مریت.",
                                'ar' => "💀 <strong>وفاة مافيا:</strong> تم إطلاق النار على المافيا <strong class='text-white'>$police_target</strong> وتوفي."
                            ];
                        }
                    }
                }

                if ($suicidal_bomb_target && $bomb_player_name) {
                    $diary[] = [
                        'en' => "💥 <strong>Bomb Detonation:</strong> Suicidal Bomb <strong class='text-white'>$bomb_player_name</strong> exploded on <strong class='text-white'>$suicidal_bomb_target</strong>. Both are dead.",
                        'ku' => "💥 <strong>تەقینا بۆمبێ:</strong> بۆمبێ خۆ کەرتی <strong class='text-white'>$bomb_player_name</strong> خۆ دگەل <strong class='text-white'>$suicidal_bomb_target</strong> تەقاند. هەردوو مرن.",
                        'ar' => "💥 <strong>تفجير الانتحاري:</strong> قام الانتحاري <strong class='text-white'>$bomb_player_name</strong> بتفجير نفسه مع <strong class='text-white'>$suicidal_bomb_target</strong>. كلاهما ماتا."
                    ];
                }

                $revealed_roles = [];
                if ($db['grave_keeper_reveal_pending'] ?? false) {
                    foreach ($db['players'] as $p) {
                        if (($p['status'] ?? '') === 'dead' || in_array($p['name'], $final_killed)) {
                            $revealed_roles[$p['name']] = $p['role'] ?? 'Citizen';
                        }
                    }
                    $db['grave_keeper_revealed_roles'] = true;
                    $db['grave_keeper_reveal_pending'] = false;
                    $db['gravedigger_reveal_pending'] = false;
                } else {
                    $db['grave_keeper_revealed_roles'] = false;
                }

                $db['last_night_report'] = [
                    'killed_names' => array_values($final_killed),
                    'saved_names' => array_values(array_unique($saved_names)),
                    'diary_entries' => $diary,
                    'revealed_roles' => $revealed_roles
                ];

                $db['phase'] = 'day';
                $db['night_actions'] = [];
                $db['grave_keeper_acted_tonight'] = false;
                $db['logs'][] = "Night {$db['day']} ended. Day {$db['day']} started.";

                check_win_conditions($db);
                save_db($db);

            } elseif ($db['phase'] === 'day') {
                $db['phase'] = 'night';
                $db['day'] = ($db['day'] ?? 1) + 1;
                $db['last_night_report'] = null;
                $db['investigation_results'] = [];
                $db['grave_keeper_revealed_roles'] = false;
                $db['grave_keeper_acted_tonight'] = false;
                $db['logs'][] = "Day " . ($db['day'] - 1) . " ended. Night {$db['day']} started.";

                check_win_conditions($db);
                save_db($db);
            }

            terminate_request("index.php");
        }

        if ($action === 'toggle_status') {
            $pid = $_POST['player_id'] ?? '';
            foreach ($db['players'] as &$p) {
                if ($p['id'] === $pid) {
                    $p['status'] = ($p['status'] === 'alive') ? 'dead' : 'alive';
                    $db['logs'][] = "Toggled status of player '{$p['name']}' to {$p['status']}.";
                    break;
                }
            }
            check_win_conditions($db);
            save_db($db);
            terminate_request("index.php");
        }

        if ($action === 'kick_player_day') {
            $pid = $_POST['player_id'] ?? '';
            foreach ($db['players'] as &$p) {
                if ($p['id'] === $pid) {
                    $p['status'] = 'dead';
                    $db['logs'][] = "Player '{$p['name']}' was voted out/kicked during Day {$db['day']}.";
                    if (($p['role'] ?? '') === 'Suicidal Bomb') {
                        $db['suicidal_bomb_triggered_by'] = $p['name'];
                        $db['logs'][] = "💣 Suicidal Bomb ('{$p['name']}') was voted out! Suicidal Bomb can now choose a player to be kicked out in revenge!";
                    }
                    break;
                }
            }
            check_win_conditions($db);
            save_db($db);
            terminate_request("index.php");
        }


        if ($action === 'suicidal_bomb_explode') {
            $pid = $_POST['target_player_id'] ?? '';
            $triggered_by = $db['suicidal_bomb_triggered_by'] ?? 'Suicidal Bomb';
            if ($pid === 'none') {
                $db['logs'][] = "🕊️ Suicidal Bomb ({$triggered_by}) decided to leave alone peacefully without taking anyone down.";
                unset($db['suicidal_bomb_triggered_by']);
                check_win_conditions($db);
                save_db($db);
            } elseif ($pid !== '') {
                $target_name = null;
                foreach ($db['players'] as &$p) {
                    if ($p['id'] === $pid && $p['status'] === 'alive') {
                        $p['status'] = 'dead';
                        $target_name = $p['name'];
                        break;
                    }
                }
                if ($target_name) {
                    $db['logs'][] = "💥 Suicidal Bomb ({$triggered_by}) triggered revenge explosion and eliminated '{$target_name}'!";
                }
                unset($db['suicidal_bomb_triggered_by']);
                check_win_conditions($db);
                save_db($db);
            }
            terminate_request("index.php");
        }
    }
}
