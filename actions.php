<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

function get_db() {
    $db = load_db();
    if (!$db || !is_array($db)) {
        $db = [
            'reset_token' => uniqid('rst_', true),
            'host_browser_id' => null,
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
    foreach ($db['players'] as $p) {
        if ($p['name'] === $target_name) {
            $role = $p['role'] ?? '';
            if (in_array($role, ['Mafia Boss', 'Mafia Doctor', 'Regular Mafia'])) {
                return 'Mafia';
            }
            if ($role === 'Deceiver') {
                return 'Citizen'; // Deceiver appears innocent to Investigator
            }
            return 'Citizen';
        }
    }
    return 'Citizen';
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
        if (empty($db['host_browser_id'])) {
            $db['host_browser_id'] = $my_browser_id;
            $db['logs'][] = "Host claimed by browser {$my_browser_id}.";
            save_db($db);
        }
        header("Location: index.php");
        exit;
    }

    if ($action === 'join_game') {
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
                $mafia_specials = ['Mafia Doctor', 'Deceiver'];
                shuffle($mafia_specials);
                
                for ($i = 0; $i < $remaining_mafia_slots; $i++) {
                    if (!empty($mafia_specials)) {
                        $deck[] = array_shift($mafia_specials);
                    } else {
                        $deck[] = 'Regular Mafia';
                    }
                }

                $citizen_specials = ['Police', 'Town Doctor', 'Investigator', 'Grave Keeper', 'Judge', 'Mirhas'];
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
            $db['reset_token'] = uniqid('rst_', true);
            $db['logs'][] = "Game reset for a Rematch.";
            save_db($db);
            header("Location: index.php");
            exit;
        }

        if ($action === 'hard_reset') {
            $db = [
                'reset_token' => uniqid('rst_', true),
                'host_browser_id' => null,
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

            if ($role === 'Investigator' && $target_name) {
                $eval_res = evaluate_investigation($target_name, $db);
                $db['investigation_results'] = [
                    ['target' => $target_name, 'result' => $eval_res]
                ];
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
                    }
                }

                if ($police_target) {
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

                $final_killed = [];
                foreach ($killed_names as $kname) {
                    foreach ($db['players'] as &$p) {
                        if ($p['name'] === $kname) {
                            if (($p['role'] ?? '') === 'Mirhas') {
                                if (!in_array($kname, $db['delayed_departure'] ?? [])) {
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
                    break;
                }
            }
            check_win_conditions($db);
            save_db($db);
            header("Location: index.php");
            exit;
        }
    }
}
