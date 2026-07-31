<?php
$db_file = __DIR__ . '/database.json';

// Fetch the cookie if it exists
$my_browser_id = $_COOKIE['mafia_browser_id'] ?? '';

// If it is empty, OR if an old cookie is not exactly 6 characters, generate a new one
if (empty($my_browser_id) || strlen($my_browser_id) !== 6) {
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
    $fp = fopen($db_file, 'r');
    if (!$fp) return null;
    // Shared lock while reading
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    if ($content === false || $content === '') {
        return null;
    }
    $decoded = json_decode($content, true);
    if (!is_array($decoded)) return null;
    return $decoded;
}

function save_db($data) {
    global $db_file;
    $tmp = $db_file . '.tmp';
    // write atomically to temp file then rename
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        // fallback: try without UNESCAPED_UNICODE
        $json = json_encode($data, JSON_PRETTY_PRINT);
    }
    // Use LOCK_EX to avoid concurrent writes to the temp file
    file_put_contents($tmp, $json, LOCK_EX);
    // Atomic rename
    rename($tmp, $db_file);
}
