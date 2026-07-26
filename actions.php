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
            'grave_keeper_acted_tonight' => false,
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
if (isset($_GET['ajax']) || (isset($_GET['action']) && $_GET['action'] === 'get_state')) {
    header('Content-Type: application/json');
    echo json_encode($db);
    exit;
}

function evaluate_investigation($target_name, $db) {
    $target_role = null;
    foreach ($db['players'] as $p) {
        if ($p['name'] === $target_name) {
            $target_role = $p['role'] ?? '';
            break;
        }
    }

    if (!$target_role) return 'Citizen';

    // Base identity: Mafia Boss & Deceiver appear as Citizen
    if ($target_role === 'Mafia Boss') {
        $res = 'Citizen';
    } elseif ($target_role === 'Deceiver') {
        $res = 'Citizen';
    } elseif (in_array($target_role, ['Mafia Doctor', 'Regular Mafia'])) {
        $res = 'Mafia';
    } else {
        $res = 'Citizen';
    }

    // Deceiver's night action effect on investigator results:
    // Deceiver can switch perceived role of any player EXCLUDING Mafia Boss
    $deceiver_target = $db['night_actions']['Deceiver'] ?? null;
    if ($deceiver_target && $deceiver_target === $target_name) {
        if ($target_role !== 'Mafia Boss') {
            $res = ($res === 'Mafia') ? 'Citizen' : 'Mafia';
        }
    }

    return $res;
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
        header("Location: index.php");
        exit;
    }

    if ($action === 'join_game') {
        if (empty($db['host_browser_id'])) {
            $_SESSION['join_error'] = __('no_host_error_desc');
            header("Location: player.php");
            exit;
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
        header("Location: player.php");
        exit;
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
            header("Location: index.php");
            exit;
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
            $db['grave_keeper_acted_tonight'] = false;
            $db['town_doctor_self_protect_count'] = 0;
            unset($db['suicidal_bomb_triggered_by']);
            $db['reset_token'] = uniqid('rst_', true);
            $db['logs'][] = "Game reset for a Rematch.";
            save_db($db);
            header("Location: index.php");
            exit;
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
                'grave_keeper_acted_tonight' => false,
                'town_doctor_self_protect_count' => 0,
                'last_night_report' => null,
                'investigation_results' => [],
                'delayed_departure' => []
            ];
            save_db($db);
            header("Location: index.php");
            exit;
        }

        if ($action === 'record_night_target') {
            $role = $_POST['role'] ?? '';
            $target_id = $_POST['target_id'] ?? '';
            $target_name = null;

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

            // Always recalculate investigation result if Investigator target is selected
            $investigator_target = $db['night_actions']['Investigator'] ?? null;
            if ($investigator_target) {
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
            header("Location: index.php");
            exit;
        }

        if ($action === 'answer_grave_keeper_reveal') {
            $answer = $_POST['reveal_answer'] ?? 'no';
            if ($answer === 'yes') {
                $db['grave_keeper_revealed_roles'] = true;
                if (($db['grave_keeper_charges'] ?? 2) > 0) {
                    $db['grave_keeper_charges'] = ($db['grave_keeper_charges'] ?? 2) - 1;
                }
            } else {
                $db['grave_keeper_revealed_roles'] = false;
            }
            $db['grave_keeper_acted_tonight'] = true;
            save_db($db);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode($db);
                exit;
            }
            header("Location: index.php");
            exit;
        }

        if ($action === 'next_phase') {
            if ($db['phase'] === 'night') {
                $mafia_target = $db['night_actions']['Mafia Boss'] ?? null;
                $mafia_doc_target = $db['night_actions']['Mafia Doctor'] ?? null;
                $town_doc_target = $db['night_actions']['Town Doctor'] ?? null;
                $police_target = $db['night_actions']['Police'] ?? null;
                $suicidal_bomb_target = $db['night_actions']['Suicidal Bomb'] ?? null;

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

                $db['last_night_report'] = [
                    'killed_names' => array_values($final_killed),
                    'saved_names' => array_values(array_unique($saved_names))
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

            header("Location: index.php");
            exit;
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
            header("Location: index.php");
            exit;
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
            header("Location: index.php");
            exit;
        }

        if ($action === 'judge_cancel_votings') {
            $db['logs'][] = "⚖️ Judge cancelled all daytime votings for Day {$db['day']}. No players were kicked today.";
            save_db($db);
            header("Location: index.php");
            exit;
        }

        if ($action === 'judge_kick_one_player') {
            $pid = $_POST['player_id'] ?? '';
            if ($pid !== '') {
                foreach ($db['players'] as &$p) {
                    if ($p['id'] === $pid) {
                        $p['status'] = 'dead';
                        $db['logs'][] = "⚖️ Judge ruled to kick only '{$p['name']}' and keep all other players in the game.";
                        if (($p['role'] ?? '') === 'Suicidal Bomb') {
                            $db['suicidal_bomb_triggered_by'] = $p['name'];
                            $db['logs'][] = "💣 Suicidal Bomb ('{$p['name']}') was voted out! Suicidal Bomb can now choose a player to be kicked out in revenge!";
                        }
                        break;
                    }
                }
                check_win_conditions($db);
                save_db($db);
            }
            header("Location: index.php");
            exit;
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
            header("Location: index.php");
            exit;
        }
    }
}
