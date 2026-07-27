<?php
require_once 'actions.php';

$mafia_roles = [
    'Mafia Boss' => ['icon' => '👑', 'desc' => 'desc_mafia_boss'],
    'Mafia Doctor' => ['icon' => '💉', 'desc' => 'desc_mafia_doctor'],
    'Deceiver' => ['icon' => '🎭', 'desc' => 'desc_deceiver'],
    'Regular Mafia' => ['icon' => '🗡️', 'desc' => 'desc_regular_mafia'],
];

$citizen_roles = [
    'Police' => ['icon' => '🔫', 'desc' => 'desc_police'],
    'Town Doctor' => ['icon' => '🩺', 'desc' => 'desc_town_doctor'],
    'Investigator' => ['icon' => '🔍', 'desc' => 'desc_investigator'],
    'Judge' => ['icon' => '⚖️', 'desc' => 'desc_judge'],
    'Grave Keeper' => ['icon' => '🪦', 'desc' => 'desc_grave_keeper'],
    'Mirhas' => ['icon' => '🛡️', 'desc' => 'desc_mirhas'],
    'Suicidal Bomb' => ['icon' => '💣', 'desc' => 'desc_suicidal_bomb'],
    'Citizen' => ['icon' => '👤', 'desc' => 'desc_citizen'],
];

$back_url = $is_host ? 'index.php' : 'player.php';
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_lang(); ?>" dir="<?php echo get_current_dir(); ?>">
<head>
<style>
/* Offline Mode: Font import removed */

:root {
    --bg-base: #09090b;
    --bg-gradient: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #09090b 70%);
    --card-bg: rgba(24, 24, 27, 0.75);
    --card-border: rgba(255, 255, 255, 0.08);
    --card-glow: 0 20px 40px -15px rgba(0, 0, 0, 0.7), 0 0 50px -20px rgba(139, 92, 246, 0.2);
    
    --text-main: #fafafa;
    --text-muted: #a1a1aa;
    
    --primary: #8b5cf6;
    --primary-hover: #7c3aed;
    --primary-glow: 0 0 20px rgba(139, 92, 246, 0.4);
    
    --accent-red: #f43f5e;
    --accent-green: #10b981;
    
    --input-bg: rgba(9, 9, 11, 0.6);
    --input-border: rgba(255, 255, 255, 0.1);
}

body {
    font-family: 'Outfit', sans-serif;
    background: var(--bg-base);
    background-image: var(--bg-gradient);
    color: var(--text-main);
    margin: 0;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    box-sizing: border-box;
    -webkit-font-smoothing: antialiased;
}

.container {
    width: 100%;
    max-width: 440px;
    background: var(--card-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--card-border);
    padding: 32px;
    border-radius: 24px;
    box-shadow: var(--card-glow);
    box-sizing: border-box;
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

h1, h2, h3 {
    margin-top: 0;
    color: #ffffff;
    text-align: center;
    letter-spacing: -0.03em;
    font-weight: 700;
}

h1 { font-size: 2.2rem; margin-bottom: 8px; background: linear-gradient(to right, #fff, #a1a1aa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
h2 { font-size: 1.5rem; margin-bottom: 16px; }
h3 { font-size: 1.1rem; margin-bottom: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

p { color: var(--text-muted); text-align: center; font-size: 0.95rem; line-height: 1.5; margin-bottom: 20px; }

button, .btn {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    padding: 16px;
    background-color: var(--primary);
    color: #ffffff;
    border: none;
    border-radius: 14px;
    font-family: 'Outfit', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 14px;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
    box-shadow: var(--primary-glow);
}

button:hover, .btn:hover {
    background-color: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 0 25px rgba(139, 92, 246, 0.6);
}

button:active, .btn:active {
    transform: translateY(0);
}

button.secondary, .btn.secondary {
    background-color: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--card-border);
    color: var(--text-main);
    box-shadow: none;
}

button.secondary:hover {
    background-color: rgba(255, 255, 255, 0.08);
    border-color: var(--primary);
    box-shadow: none;
}

button.danger, .btn.danger {
    background-color: var(--accent-red);
    box-shadow: 0 0 20px rgba(244, 63, 94, 0.4);
}

button.danger:hover {
    background-color: #e11d48;
    box-shadow: 0 0 25px rgba(244, 63, 94, 0.6);
}

input[type="text"], select {
    width: 100%;
    padding: 16px;
    background: var(--input-bg);
    border: 1px solid var(--input-border);
    color: #fff;
    border-radius: 14px;
    font-family: 'Outfit', sans-serif;
    font-size: 1rem;
    box-sizing: border-box;
    margin-top: 6px;
    margin-bottom: 16px;
    outline: none;
    transition: all 0.2s ease;
}

input[type="text"]:focus {
    border-color: var(--primary);
    background: rgba(9, 9, 11, 0.9);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}

.role-grid {
    display: grid;
    grid-template-columns: 1fr 70px;
    gap: 10px 14px;
    align-items: center;
    background: rgba(0, 0, 0, 0.25);
    padding: 16px;
    border-radius: 16px;
    border: 1px solid var(--card-border);
    margin-bottom: 16px;
    max-height: 220px;
    overflow-y: auto;
}

.role-grid label {
    font-size: 0.9rem;
    color: var(--text-main);
    font-weight: 500;
}

.role-grid input {
    text-align: center;
    margin: 0;
    padding: 10px;
    font-weight: 600;
}

.player-list {
    list-style: none;
    padding: 0;
    max-height: 200px;
    overflow-y: auto;
    margin: 16px 0;
    border: 1px solid var(--card-border);
    border-radius: 16px;
    background: rgba(0, 0, 0, 0.25);
}

.player-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    border-bottom: 1px solid var(--card-border);
    font-size: 0.95rem;
    font-weight: 500;
}

.player-item:last-child {
    border-bottom: none;
}

.center { text-align: center; }

.dead {
    color: var(--accent-red);
    font-weight: 800;
    font-size: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-shadow: 0 0 20px rgba(244, 63, 94, 0.3);
}

.badge {
    background: rgba(139, 92, 246, 0.15);
    color: #c4b5fd;
    border: 1px solid rgba(139, 92, 246, 0.3);
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    display: inline-block;
}

hr {
    border: none;
    border-top: 1px solid var(--card-border);
    margin: 24px 0;
}
</style>
<script>
// Helper script for Mafia Game UI
console.log("Mafia Game Client initialized");

// Placeholder for WebSocket implementation
const socket = new WebSocket('ws://' + window.location.host + '/ws');
socket.onopen = () => console.log('WebSocket connected');
socket.onmessage = (event) => console.log('WebSocket message:', event.data);
socket.onclose = () => console.log('WebSocket disconnected');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_title_roles'); ?></title>
    <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/tailwind.js?v=2"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 md:p-6 font-sans">
    
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Top Navigation & Language Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-center bg-slate-900 border border-slate-800 p-4 rounded-xl shadow-lg gap-4">
            <a href="<?php echo $back_url; ?>" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition border border-slate-700 flex items-center gap-2">
                <?php echo __('back_to_game'); ?>
            </a>
            
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">🌐 <?php echo __('language'); ?>:</span>
                <?php render_language_selector(); ?>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-slate-900 border border-slate-800 p-6 rounded-xl shadow-xl text-center space-y-2">
            <h1 class="text-2xl md:text-3xl font-black text-amber-400 uppercase tracking-wider">
                <?php echo __('roles_guide_header'); ?>
            </h1>
            <p class="text-xs md:text-sm text-slate-400 max-w-2xl mx-auto">
                <?php echo __('roles_guide_subheader'); ?>
            </p>
        </header>

        <!-- MAFIA FACTION SECTION -->
        <section class="space-y-4">
            <div class="flex items-center gap-3 border-b border-rose-900/60 pb-2">
                <span class="text-2xl">🔪</span>
                <h2 class="text-xl font-black text-rose-500 uppercase tracking-wider">
                    <?php echo __('team_mafia'); ?>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($mafia_roles as $role_key => $info): ?>
                    <div class="bg-slate-900/90 border border-rose-900/50 hover:border-rose-600/80 p-5 rounded-xl space-y-3 transition shadow-lg">
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl"><?php echo $info['icon']; ?></span>
                                <h3 class="font-black text-base text-rose-400">
                                    <?php echo get_role_label($role_key); ?>
                                </h3>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-950 text-rose-300 border border-rose-800 shrink-0">
                                <?php echo __('team_mafia'); ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            <?php echo __($info['desc']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- CITIZENS FACTION SECTION -->
        <section class="space-y-4 pt-4">
            <div class="flex items-center gap-3 border-b border-emerald-900/60 pb-2">
                <span class="text-2xl">🛡️</span>
                <h2 class="text-xl font-black text-emerald-400 uppercase tracking-wider">
                    <?php echo __('team_citizens'); ?>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($citizen_roles as $role_key => $info): ?>
                    <div class="bg-slate-900/90 border border-emerald-900/40 hover:border-emerald-600/80 p-5 rounded-xl space-y-3 transition shadow-lg flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-2xl"><?php echo $info['icon']; ?></span>
                                    <h3 class="font-black text-base text-emerald-300">
                                        <?php echo get_role_label($role_key); ?>
                                    </h3>
                                </div>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950 text-emerald-300 border border-emerald-800 shrink-0">
                                    <?php echo __('team_citizens'); ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                <?php echo __($info['desc']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="text-center pt-6 pb-4">
            <a href="<?php echo $back_url; ?>" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest px-8 py-3 rounded-xl shadow-xl transition">
                <?php echo __('back_to_game'); ?>
            </a>
        </div>

    </div>
</body>
</html>
