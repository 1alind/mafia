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
            'roles_shared' => false,
            'players' => []
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
    echo json_encode([
        'reset_token' => $db['reset_token'] ?? '',
        'roles_shared' => $db['roles_shared'] ?? false,
        'players' => $db['players'] ?? []
    ]);
    exit;
}

function terminate_request($target) {
    global $db;
    if (isset($_POST['ajax']) || isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
        header('Content-Type: application/json');
        echo json_encode([
            'reset_token' => $db['reset_token'] ?? '',
            'roles_shared' => $db['roles_shared'] ?? false,
            'players' => $db['players'] ?? []
        ]);
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
                    'role' => 'Pending'
                ];
                $_SESSION['player_id'] = $pid;
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
                }

                $db['roles_shared'] = true;
                $db['reset_token'] = uniqid('rst_', true);
                save_db($db);
            }
            terminate_request("index.php");
        }

        if ($action === 'hide_roles') {
            foreach ($db['players'] as &$p) {
                $p['role'] = 'Pending';
            }
            $db['roles_shared'] = false;
            $db['reset_token'] = uniqid('rst_', true);
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
                    'role' => 'Pending'
                ];
            }
            save_db($db);
            terminate_request("index.php");
        }

        if ($action === 'remove_player_setup') {
            $pid = $_POST['player_id'] ?? '';
            foreach ($db['players'] as $key => $p) {
                if ($p['id'] === $pid) {
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
                'players' => [],
                'roles_shared' => false
            ];
            save_db($db);
            terminate_request("index.php");
        }
    }
}
