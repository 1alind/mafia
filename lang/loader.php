<?php
// Translation Loader & Helper Functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allowed languages
$available_langs = [
    'en' => ['name' => 'English', 'flag' => '🇬🇧'],
    'ar' => ['name' => 'العربية', 'flag' => '🇮🇶'],
    'ku' => ['name' => 'کوردی (بادینی)', 'flag' => '☀️']
];

// Handle language selection via URL parameter or cookie
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_langs)) {
    $current_lang = $_GET['lang'];
    setcookie('mafia_lang', $current_lang, time() + (86400 * 365), "/");
    $_COOKIE['mafia_lang'] = $current_lang;
} else {
    $current_lang = $_COOKIE['mafia_lang'] ?? 'ku';
    if (!array_key_exists($current_lang, $available_langs)) {
        $current_lang = 'ku';
    }
}

// Load language translation array
$lang_file = __DIR__ . '/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    $translations = require $lang_file;
} else {
    $translations = require __DIR__ . '/ku.php';
}

/**
 * Translate a key with optional sprintf arguments
 */
function __($key, ...$args) {
    global $translations;
    $text = $translations[$key] ?? $key;
    if (!empty($args)) {
        return sprintf($text, ...$args);
    }
    return $text;
}

/**
 * Translate game role names
 */
function get_role_label($role) {
    if (empty($role) || $role === 'Pending') {
        return __('role_pending');
    }

    $map = [
        'Mafia Boss' => 'role_mafia_boss',
        'Mafia Doctor' => 'role_mafia_doctor',
        'Deceiver' => 'role_deceiver',
        'Regular Mafia' => 'role_regular_mafia',
        'Police' => 'role_police',
        'Town Doctor' => 'role_town_doctor',
        'Investigator' => 'role_investigator',
        'Judge' => 'role_judge',
        'Grave Keeper' => 'role_grave_keeper',
        'Mirhas' => 'role_mirhas',
        'Suicidal Bomb' => 'role_suicidal_bomb',
        'Citizen' => 'role_citizen',
        'Pending' => 'role_pending',
        'Mafia' => 'team_mafia',
    ];

    if (isset($map[$role])) {
        return __($map[$role]);
    }

    return $role;
}

/**
 * Translate team winner names
 */
function get_winner_label($winner) {
    if ($winner === 'Citizens') {
        return __('team_citizens');
    }
    if ($winner === 'Mafia') {
        return __('team_mafia');
    }
    return $winner;
}

/**
 * Get active text direction ('ltr' or 'rtl')
 */
function get_current_dir() {
    global $translations;
    return $translations['dir'] ?? 'ltr';
}

/**
 * Get active language code
 */
function get_current_lang() {
    global $current_lang;
    return $current_lang;
}

/**
 * Render language selector widget
 */
function render_language_selector() {
    global $available_langs, $current_lang;
    
    // Build URL maintaining existing query parameters except 'lang'
    $queryParams = $_GET;
    unset($queryParams['lang']);
    
    echo '<div class="flex items-center gap-1 bg-slate-900 border border-slate-700/80 p-1 rounded-lg text-xs font-bold">';
    foreach ($available_langs as $code => $info) {
        $queryParams['lang'] = $code;
        $url = '?' . http_build_query($queryParams);
        $activeClass = ($code === $current_lang) 
            ? 'bg-rose-600 text-white shadow' 
            : 'text-slate-400 hover:text-white hover:bg-slate-800';
            
        echo '<a href="' . htmlspecialchars($url) . '" class="px-2.5 py-1 rounded transition flex items-center gap-1.5 ' . $activeClass . '">';
        echo '<span>' . $info['flag'] . '</span>';
        echo '<span>' . htmlspecialchars($info['name']) . '</span>';
        echo '</a>';
    }
    echo '</div>';
}
