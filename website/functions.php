<?php
$db_file = __DIR__ . '/database.json';

// Fetch the cookie if it exists
$my_browser_id = $_COOKIE['mafia_browser_id'] ?? '';

// If it is empty, OR if an old cookie is longer than 6 characters, generate a new one
if (empty($my_browser_id) || strlen($my_browser_id) > 6) {
    // Generates a strict 6-character random hex string (e.g., 'a4b7f2')
    $my_browser_id = substr(bin2hex(random_bytes(3)), 0, 6);
    
    setcookie('mafia_browser_id', $my_browser_id, time() + (86400 * 365), "/", "", false, true);
    $_COOKIE['mafia_browser_id'] = $my_browser_id;
}

function load_db() {
    global $db_file;
    if (!file_exists($db_file)) {
        return null;
    }
    $content = @file_get_contents($db_file);
    if (!$content) {
        return null;
    }
    return json_decode($content, true);
}

function save_db($data) {
    global $db_file;
    @file_put_contents($db_file, json_encode($data, JSON_PRETTY_PRINT));
}
