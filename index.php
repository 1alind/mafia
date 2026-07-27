<?php
require_once 'actions.php';

if (!$is_host && !$needs_host_claim) {
    header("Location: player.php");
    exit;
}

$all_game_roles = [
    'Mafia',
    'Mafia Doctor',
    'Deceiver',
    'Regular Mafia',
    'Police',
    'Town Doctor',
    'Investigator',
    'Judge',
    'Grave Keeper',
    'Mirhas',
    'Citizen'
];

$role_i18n_map = [];
foreach ($all_game_roles as $r_name) {
    $role_i18n_map[$r_name] = get_role_label($r_name);
}
$role_i18n_map['Pending'] = get_role_label('Pending');
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

document.addEventListener('submit', async function(e) {
    if (e.target.tagName === 'FORM') {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Add the submitter button's name/value if it exists
        if (e.submitter && e.submitter.name) {
            formData.append(e.submitter.name, e.submitter.value);
        }
        
        // Handle record_night_target locally
        if (formData.get('action') === 'record_night_target') {
            if (!window.localNightActions) window.localNightActions = {};
            window.localNightActions[formData.get('role')] = formData.get('target_id');
            console.log('Action stored locally:', window.localNightActions);
            const btn = e.target.querySelector('button');
            if (btn) {
                const originalText = btn.innerText;
                btn.innerText = '✅ Saved';
                setTimeout(() => btn.innerText = originalText, 2000);
            }
            return;
        }

        // Special handling for Next Phase to submit local actions
        if (formData.get('action') === 'next_phase' && window.localNightActions && Object.keys(window.localNightActions).length > 0) {
             const bulkFormData = new FormData();
             bulkFormData.append('action', 'submit_all_night_actions');
             bulkFormData.append('actions', JSON.stringify(window.localNightActions));
             bulkFormData.append('ajax', '1');
             
             await fetch(window.location.href, {
                 method: 'POST',
                 body: bulkFormData
             });
             window.localNightActions = {}; // Clear after successful submit
        }
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            console.log('Form submitted successfully');
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Only update the container content to avoid full page reload feel
            const newContainer = doc.querySelector('.container');
            const currentContainer = document.querySelector('.container');
            if (newContainer && currentContainer) {
                currentContainer.innerHTML = newContainer.innerHTML;
            }
        }
    }
});
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_title_host'); ?></title>
    <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/tailwind.js?v=2"></script>
    <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/qrcode.min.js?v=2"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 md:p-6 font-sans">
    
    <div class="max-w-6xl mx-auto space-y-6">

        <?php
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $ip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
            $join_url = $protocol . "://" . $host . dirname($_SERVER['PHP_SELF']);
            if (substr($join_url, -1) !== '/') {
                $join_url .= '/';
            }
        ?>
        <!-- Server Connection Info Banner -->
        <div class="bg-slate-900 border border-slate-800 p-4 rounded-xl shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">📡</div>
                <div>
                    <h2 class="text-sm font-bold text-slate-200">Connect to Game</h2>
                    <p class="text-xs text-slate-400 font-mono mt-1">Domain: <span class="text-indigo-400"><?php echo htmlspecialchars($host); ?></span> | IP: <span class="text-indigo-400"><?php echo htmlspecialchars($ip); ?></span></p>
                    <p class="text-xs text-slate-400 font-mono">URL: <span class="text-amber-400"><?php echo htmlspecialchars($join_url); ?></span></p>
                </div>
            </div>
            <div>
                <button onclick="document.getElementById('qr-modal').classList.toggle('hidden'); if(!window.qrCreated){ new QRCode(document.getElementById('qrcode-container'), '<?php echo addslashes($join_url); ?>'); window.qrCreated=true; }" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2">
                    <span>📱</span> Show QR Code
                </button>
            </div>
        </div>

        <!-- QR Code Modal -->
        <div id="qr-modal" class="hidden flex justify-center mb-6">
            <div class="bg-white p-4 rounded-xl shadow-xl flex flex-col items-center">
                <div id="qrcode-container" class="mb-2"></div>
                <p class="text-xs font-bold text-slate-800 uppercase tracking-widest text-center">Scan to Join</p>
            </div>
        </div>

        <!-- Language Selector Header Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-center bg-slate-900 border border-slate-800 p-3 rounded-xl shadow-lg gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 text-xs text-slate-400 font-bold uppercase tracking-wider">
                    🌐 <?php echo __('language'); ?>:
                    <?php render_language_selector(); ?>
                </div>
                <!-- Sound Mute Button -->
                <button type="button" id="mute-btn" onclick="toggleMute()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-lg px-3 py-1.5 text-xs font-black transition flex items-center gap-1.5 shadow-sm">
                    <span id="mute-icon">🔊</span>
                    <span id="mute-text">Sounds: ON</span>
                </button>
            </div>
            <div>
                <a href="roles.php" class="bg-indigo-900/80 hover:bg-indigo-800 text-indigo-200 border border-indigo-700/80 px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase transition flex items-center gap-1.5 shadow">
                    <?php echo __('view_roles_guide'); ?>
                </a>
            </div>
        </div>

        <!-- DISCUSSION & PHASE TIMER -->
        <div class="bg-slate-900 border border-slate-800 p-4 rounded-xl shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="p-3 bg-indigo-950/60 rounded-xl border border-indigo-900/60 text-xl">
                    ⏱️
                </div>
                <div class="space-y-0.5">
                    <h3 class="text-xs font-black uppercase text-indigo-400 tracking-wider">
                        <?php echo get_current_lang() === 'ku' ? 'دەمژمێرا دانوستاندنێ یاریێ' : (get_current_lang() === 'ar' ? 'مؤقت النقاش واللعب' : 'Discussion & Game Timer'); ?>
                    </h3>
                    <p class="text-[10px] text-slate-400">
                        <?php echo get_current_lang() === 'ku' ? 'کۆنترۆلا کاتژمێرا باژێڕی بکە بۆ دانوستاندنان دگەل لێدانا دەنگان.' : (get_current_lang() === 'ar' ? 'تحكم في وقت النقاش مع منبهات صوتية.' : 'Control the countdown time for discussion with audio alerts.'); ?>
                    </p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Preset Buttons -->
                <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-lg border border-slate-850">
                    <button type="button" onclick="setTimerPreset(30)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">30s</button>
                    <button type="button" onclick="setTimerPreset(60)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">1m</button>
                    <button type="button" onclick="setTimerPreset(120)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">2m</button>
                    <button type="button" onclick="setTimerPreset(180)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">3m</button>
                    <button type="button" onclick="setTimerPreset(300)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">5m</button>
                    <button type="button" onclick="setTimerPreset(600)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">10m</button>
                </div>
                
                <!-- Main Timer Display & Controls -->
                <div class="flex items-center gap-3 bg-slate-950 px-4 py-2 rounded-xl border border-slate-800 min-w-[220px] justify-between">
                    <!-- Time Display -->
                    <div id="timer-display" class="text-2xl font-black font-mono tracking-wider text-indigo-400">02:00</div>
                    
                    <!-- Controls -->
                    <div class="flex items-center gap-2">
                        <!-- Play/Pause Button -->
                        <button type="button" id="timer-play-btn" onclick="toggleTimer()" class="p-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition text-xs font-bold" title="Start/Pause">
                            ▶️
                        </button>
                        <!-- Reset Button -->
                        <button type="button" onclick="resetTimer()" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-xs font-bold" title="Reset">
                            🔄
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- BIG VICTORY BANNER (If Game Over) -->
        <?php if (!empty($db['winner'])): ?>
            <div class="bg-gradient-to-r <?php echo $db['winner'] === 'Citizens' ? 'from-emerald-900 via-teal-900 to-emerald-950 border-emerald-500 text-emerald-300' : 'from-rose-900 via-red-950 to-rose-950 border-rose-500 text-rose-300'; ?> border-4 p-8 rounded-2xl shadow-2xl text-center space-y-4 animate-pulse">
                <div class="text-5xl">🏆</div>
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-wider text-white drop-shadow-lg">
                    <?php echo $db['winner'] === 'Citizens' ? __('citizens_win_title') : __('mafia_win_title'); ?>
                </h1>
                <p class="text-sm md:text-base font-semibold text-slate-200">
                    <?php echo $db['winner'] === 'Citizens' ? __('citizens_win_desc') : __('mafia_win_desc'); ?>
                </p>
                <div class="pt-2">
                    <form method="POST">
                        <input type="hidden" name="action" value="hide_roles">
                        <button type="submit" class="bg-white text-slate-950 hover:bg-slate-200 px-8 py-3 rounded-xl font-black text-sm uppercase tracking-widest shadow-2xl transition transform hover:scale-105">
                            <?php echo __('start_new_rematch'); ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Header -->
        <header class="bg-slate-900 border border-slate-800 p-6 rounded-xl flex flex-col sm:flex-row justify-between items-center gap-4 shadow-xl">
            <div>
                <h1 class="text-2xl font-black text-rose-500 uppercase tracking-wider"><?php echo __('host_panel_title'); ?></h1>
                <p class="text-xs text-slate-400 mt-1"><?php echo __('host_panel_subtitle'); ?></p>
            </div>
            
            <?php if (!$needs_host_claim && $is_host): ?>
                <div class="flex items-center gap-3 flex-wrap justify-end">
                    <?php if ($db['roles_shared'] ?? false): ?>
                        <form method="POST" onsubmit="return confirm('<?php echo htmlspecialchars(__('confirm_rematch'), ENT_QUOTES); ?>');">
                            <input type="hidden" name="action" value="hide_roles">
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wider shadow transition">
                                <?php echo __('rematch'); ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return confirm('<?php echo htmlspecialchars(__('confirm_reset_session'), ENT_QUOTES); ?>');">
                        <input type="hidden" name="action" value="hard_reset">
                        <button type="submit" class="text-xs text-rose-400 hover:underline bg-slate-800 px-3 py-1.5 rounded border border-slate-700">
                            <?php echo __('reset_session'); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($needs_host_claim): ?>
            <!-- Host Claim Password Form -->
            <div class="bg-slate-900 border-2 border-rose-900/80 p-8 rounded-2xl max-w-md mx-auto text-center space-y-5 shadow-2xl">
                <div class="text-4xl">🔑</div>
                <div class="space-y-1">
                    <h2 class="text-xl font-black text-rose-400 uppercase tracking-wider">
                        <?php echo __('claim_host_role'); ?>
                    </h2>
                    <p class="text-xs text-slate-400">
                        <?php echo __('host_password_label'); ?>
                    </p>
                </div>

                <?php if (!empty($_SESSION['host_error'])): ?>
                    <div class="bg-rose-950/80 border border-rose-800 text-rose-300 p-3 rounded-lg text-xs font-bold">
                        <?php 
                            echo htmlspecialchars($_SESSION['host_error']); 
                            unset($_SESSION['host_error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="claim_host">
                    <input 
                        type="password" 
                        name="host_password" 
                        required 
                        placeholder="<?php echo htmlspecialchars(__('enter_host_password')); ?>"
                        class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 rounded-xl px-4 py-3 text-center text-sm font-bold text-slate-100 placeholder-slate-500 outline-none transition"
                    >
                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-black py-3 rounded-xl text-xs uppercase tracking-widest shadow-lg transition">
                        <?php echo __('claim_host_btn'); ?>
                    </button>
                </form>

                <p class="text-[11px] text-slate-500 italic">
                    💡 <?php echo __('default_password_hint'); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!$needs_host_claim && $is_host): ?>
            
            <!-- NIGHT PHASE GUIDED ASSISTANT -->
            <?php if ($db['phase'] === 'night' && empty($db['winner'])): ?>
                <div class="bg-indigo-950/60 border-2 border-indigo-500/60 p-6 rounded-xl space-y-5 shadow-2xl">
                    <div class="flex justify-between items-center border-b border-indigo-900/80 pb-3">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-widest text-indigo-400"><?php echo __('night_control_center'); ?></span>
                            <h2 class="text-xl font-black text-white mt-0.5"><?php echo __('call_roles_record_actions'); ?></h2>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="next_phase">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded font-bold text-xs uppercase tracking-wider shadow transition">
                                <?php echo __('end_night_start_day'); ?>
                            </button>
                        </form>
                    </div>

                    <p class="text-xs text-amber-300 bg-amber-950/40 border border-amber-900/60 p-3 rounded-lg">
                        ⚠️ <strong><?php echo __('host_calling_rule'); ?></strong> 
                        <?php if ($db['grave_keeper_revealed_roles'] ?? false): ?>
                            <?php echo __('gk_skip_calling_rule'); ?>
                        <?php else: ?>
                            <?php echo __('gk_call_all_rule'); ?>
                        <?php endif; ?>
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php 
                        $active_game_roles = [];
                        $role_to_player_name = [];
                        foreach ($db['players'] as $p) {
                            if (!empty($p['role']) && $p['role'] !== 'Pending' && $p['role'] !== 'Citizen') {
                                $active_game_roles[$p['role']] = $p['name'];
                            }
                        }

                        $gk_charges = $db['grave_keeper_charges'] ?? 2;
                        $has_grave_keeper = isset($active_game_roles['Grave Keeper']);
                        $gk_revealed = $db['grave_keeper_revealed_roles'] ?? false;
                        $gk_acted_tonight = $db['grave_keeper_acted_tonight'] ?? false;

                        $is_gk_dead = false;
                        foreach ($db['players'] as $p) {
                            if (($p['role'] ?? '') === 'Grave Keeper') {
                                if ($p['status'] === 'dead' || in_array($p['name'], $db['delayed_departure'] ?? [])) {
                                    $is_gk_dead = true;
                                }
                                break;
                            }
                        }

                        // Check if Gravedigger is alive and has remaining charges
                        $call_grave_keeper_tonight = ($has_grave_keeper && !$is_gk_dead && $gk_charges > 0);

                        foreach ($all_game_roles as $role): 
                            if (in_array($role, ['Judge', 'Citizen', 'Mirhas', 'Regular Mafia'])) continue;
                            
                            if ($role === 'Grave Keeper') {
                                if (!$call_grave_keeper_tonight) continue; 
                            } elseif ($role === 'Mafia') {
                                // Find if there is any active/alive Mafia
                                $mafia_active = false;
                                $mafia_alive = false;
                                foreach ($db['players'] as $p) {
                                    if (in_array($p['role'] ?? '', ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'])) {
                                        $mafia_active = true;
                                        if ($p['status'] === 'alive' && !in_array($p['name'], $db['delayed_departure'] ?? [])) {
                                            $mafia_alive = true;
                                        }
                                    }
                                }
                                if (!$mafia_active) continue; 
                                if ($gk_revealed && !$mafia_alive) continue;
                            } else {
                                if (!isset($active_game_roles[$role])) continue; 

                                if ($gk_revealed) {
                                    $player_name_for_role = $active_game_roles[$role];
                                    $is_role_dead_or_out = false;
                                    foreach ($db['players'] as $pl) {
                                        if ($pl['name'] === $player_name_for_role && ($pl['status'] === 'dead' || in_array($pl['name'], $db['delayed_departure'] ?? []))) {
                                            $is_role_dead_or_out = true;
                                            break;
                                        }
                                    }
                                    if ($is_role_dead_or_out) continue; 
                                }
                            }
                        ?>
                            <div class="bg-slate-900 border border-slate-800 p-4 rounded-xl flex flex-col justify-between space-y-4" data-role-card="<?php echo $role; ?>">
                                <div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-black text-sm text-rose-400">
                                            <?php 
                                            if ($role === 'Grave Keeper') {
                                                echo get_role_label('Grave Keeper') . ' (' . __('charges_left') . " $gk_charges/2)";
                                            } else {
                                                echo get_role_label($role);
                                            }
                                            ?>
                                        </span>
                                        <?php if ($role === 'Grave Keeper'): ?>
                                            <span class="status-badge text-[10px] <?php echo $gk_acted_tonight ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-indigo-950 text-indigo-400 border border-indigo-800'; ?> px-2 py-0.5 rounded font-bold uppercase">
                                                <?php echo $gk_acted_tonight ? __('decided') : __('host_prompt'); ?>
                                            </span>
                                        <?php else: ?>
                                            <?php 
                                            $recorded_target = $db['night_actions'][$role] ?? null;
                                            ?>
                                            <span class="status-badge text-[10px] px-2 py-0.5 rounded font-bold uppercase <?php echo $recorded_target ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-amber-950 text-amber-400 border border-amber-800'; ?>">
                                                <?php echo $recorded_target ? __('recorded') : __('pending'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <?php 
                                        if ($role === 'Grave Keeper') {
                                            echo $gk_acted_tonight ? __('gk_already_decided') : __('select_grave_keeper_action');
                                        } elseif ($role === 'Mafia') {
                                            echo __('select_mafia_boss_target');
                                        } elseif ($role === 'Deceiver') {
                                            echo __('select_deceiver_target');
                                        } elseif ($role === 'Mafia Doctor') {
                                            echo __('select_mafia_doc_target');
                                        } elseif ($role === 'Police') {
                                            echo __('select_police_target');
                                        } elseif ($role === 'Town Doctor') {
                                            echo __('select_town_doc_target', ($db['town_doctor_self_protect_count'] ?? 0));
                                        } elseif ($role === 'Investigator') {
                                            echo __('select_investigator_target');
                                        } elseif ($role === 'Suicidal Bomb') {
                                            echo __('select_suicidal_bomb_target');
                                        } else {
                                            echo __('select_target');
                                        }
                                        ?>
                                    </p>
                                </div>

                                <form onsubmit="handleNightActionSubmit(event, '<?php echo $role; ?>')" class="space-y-2">
                                    <?php if ($role === 'Grave Keeper'): ?>
                                        <input type="hidden" name="action" value="answer_grave_keeper_reveal">
                                        <div class="space-y-2 <?php echo $gk_acted_tonight ? 'hidden' : ''; ?>" id="gk-buttons-container">
                                            <select name="reveal_answer" class="w-full bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded p-2 focus:outline-none focus:border-rose-500">
                                                <option value=""><?php echo __('make_selection'); ?></option>
                                                <?php 
                                                $lang = get_current_lang();
                                                $disable_reason = '';
                                                if ($is_gk_dead) {
                                                    $disable_reason = ' (' . __('dead') . ')';
                                                } elseif ($gk_charges <= 0) {
                                                    $disable_reason = ' (' . ($lang === 'ku' ? 'چ جاران نەماینە' : ($lang === 'ar' ? 'لا توجد محاولات' : 'No charges left')) . ')';
                                                }
                                                ?>
                                                <?php if (!($is_gk_dead || $gk_charges <= 0)): ?>
                                                    <option value="yes"><?php echo __('gk_option_yes'); ?></option>
                                                <?php endif; ?>
                                                <option value="no"><?php echo __('gk_option_no'); ?></option>
                                            </select>
                                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 rounded uppercase tracking-wider transition shadow">
                                                <?php echo __('confirm_decision'); ?>
                                            </button>
                                        </div>
                                        <?php if ($gk_acted_tonight): ?>
                                            <div class="text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center">
                                                <?php echo __('gk_decision_recorded'); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="record_night_target">
                                        <input type="hidden" name="role" value="<?php echo $role; ?>">
                                        <select name="target_id" class="target-select w-full bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded p-2 focus:outline-none focus:border-rose-500">
                                            <option value=""><?php echo __('none_no_selection'); ?></option>
                                            <?php 
                                            $town_doc_self_count = $db['town_doctor_self_protect_count'] ?? 0;
                                            $mafia_boss_name = $active_game_roles['Mafia Boss'] ?? '';

                                            foreach ($db['players'] as $p): 
                                                if ($p['status'] !== 'alive') continue;

                                                if ($role === 'Mafia Doctor') {
                                                    $is_target_mafia = in_array($p['role'], ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia']);
                                                    if (!$is_target_mafia) continue;
                                                }

                                                if ($role === 'Police' && $p['name'] === ($active_game_roles['Police'] ?? '')) continue;
                                                if ($role === 'Investigator' && $p['name'] === ($active_game_roles['Investigator'] ?? '')) continue;
                                                if ($role === 'Suicidal Bomb' && $p['name'] === ($active_game_roles['Suicidal Bomb'] ?? '')) continue;

                                                if ($role === 'Town Doctor' && $p['name'] === ($active_game_roles['Town Doctor'] ?? '') && $town_doc_self_count >= 2) {
                                                    continue;
                                                }

                                                if ($role === 'Deceiver' && $p['name'] === $mafia_boss_name) continue;
                                            ?>
                                                <option value="<?php echo $p['id']; ?>" <?php echo ($recorded_target === $p['name']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($p['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <div class="flex gap-2">
                                            <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 rounded uppercase tracking-wider transition shadow">
                                                <?php echo __('confirm'); ?>
                                            </button>
                                            <button type="button" onclick="cancelNightAction('<?php echo $role; ?>')" class="cancel-btn bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-800 font-bold text-xs px-3 py-2 rounded uppercase tracking-wider transition <?php echo $recorded_target ? '' : 'hidden'; ?>" title="<?php echo __('cancel'); ?>">
                                                <?php echo __('cancel'); ?>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </form>

                                <?php if ($role !== 'Grave Keeper'): ?>
                                    <?php $recorded_target = $db['night_actions'][$role] ?? null; ?>
                                    <div class="result-container space-y-1 <?php echo $recorded_target ? '' : 'hidden'; ?>">
                                        <div class="selected-text text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center truncate">
                                            <?php echo __('selected'); ?> <?php echo htmlspecialchars($recorded_target ?? ''); ?>
                                        </div>

                                        <?php if ($role === 'Investigator'): 
                                            $eval_res = $recorded_target ? evaluate_investigation($recorded_target, $db) : '';
                                        ?>
                                            <div class="investigator-result text-xs font-bold p-2 rounded border text-center <?php echo $eval_res === 'Mafia' ? 'bg-rose-950/80 border-rose-800 text-rose-300 animate-pulse' : 'bg-sky-950/80 border-sky-800 text-sky-300'; ?>">
                                                <?php echo __('investigator_result'); ?> <span class="underline uppercase"><?php echo $eval_res === 'Mafia' ? get_role_label('Regular Mafia') : get_role_label('Citizen'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- DAYBREAK REPORT BANNER -->
            <?php if ($db['phase'] === 'day' && !empty($db['last_night_report']) && empty($db['winner'])): 
                $report = $db['last_night_report'];
                $killed_list = $report['killed_names'] ?? [];
            ?>
                <div class="bg-slate-900 border-2 border-rose-500/50 p-6 rounded-xl space-y-3 shadow-2xl">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">☀️</span>
                        <h2 class="text-lg font-black uppercase text-rose-400"><?php echo __('day_morning_report', $db['day']); ?></h2>
                    </div>
                    
                    <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 space-y-4 text-sm">
                        <!-- 1. Players leaving the game list -->
                        <?php if (empty($killed_list)): ?>
                            <p class="text-emerald-400 font-bold text-base">
                                <?php echo __('no_players_leaving'); ?>
                            </p>
                        <?php else: ?>
                            <?php 
                            $escaped_names = array_map(function($n) {
                                return '<span class="text-white underline font-black">' . htmlspecialchars($n) . '</span>';
                            }, $killed_list);
                            
                            $lang = get_current_lang();
                            if ($lang === 'ku') {
                                if (count($escaped_names) === 1) {
                                    $sentence = $escaped_names[0] . ' دێ ژ یاریێ دەرکەڤیت.';
                                } else {
                                    $last = array_pop($escaped_names);
                                    $sentence = implode(' و ', $escaped_names) . ' و ' . $last . ' دێ ژ یاریێ دەرکەڤن.';
                                }
                            } elseif ($lang === 'ar') {
                                if (count($escaped_names) === 1) {
                                    $sentence = $escaped_names[0] . ' سيغادر اللعبة.';
                                } else {
                                    $last = array_pop($escaped_names);
                                    $sentence = implode(' و ', $escaped_names) . ' و ' . $last . ' سيغادرون اللعبة.';
                                }
                            } else {
                                // English
                                if (count($escaped_names) === 1) {
                                    $sentence = $escaped_names[0] . ' will leave the game.';
                                } else {
                                    $last = array_pop($escaped_names);
                                    $sentence = implode(', ', $escaped_names) . ' and ' . $last . ' will leave the game.';
                                }
                            }
                            ?>
                            <p class="text-rose-400 font-black text-base leading-relaxed">
                                ⚠️ <?php echo $sentence; ?>
                            </p>
                        <?php endif; ?>

                        <!-- 2. Grave Keeper Morning Section -->
                        <div class="border-t border-slate-800 pt-3 mt-3 space-y-2">
                            <?php if ($db['grave_keeper_revealed_roles'] ?? false): ?>
                                <p class="text-xs text-emerald-400 font-bold">
                                    <?php 
                                    if ($lang === 'ku') {
                                        echo '🪦 گۆڕهەڵکەن بڕیاردا کو ڕۆلێن یاریزانێن شەڤا بڕی مرین ئاشکرا بکەت:';
                                    } elseif ($lang === 'ar') {
                                        echo '🪦 قرر حارس القبور كشف أدوار اللاعبين الذين ماتوا الليلة الماضية:';
                                    } else {
                                        echo '🪦 Grave keeper decided to reveal the roles of players who died last night:';
                                    }
                                    ?>
                                </p>
                                <div class="mt-1 bg-indigo-950/40 border border-indigo-900/60 p-3 rounded-lg font-bold">
                                    <?php 
                                    $revealed_roles = $report['revealed_roles'] ?? [];
                                    if (!empty($revealed_roles)) {
                                        $display_parts = [];
                                        foreach ($revealed_roles as $name => $role_name) {
                                            $display_parts[] = '<span class="text-white">' . htmlspecialchars($name) . '</span>: <span class="text-rose-300 underline">' . htmlspecialchars(get_role_label($role_name)) . '</span>';
                                        }
                                        echo '<div class="text-xs space-y-1">' . implode('<br>', $display_parts) . '</div>';
                                    } else {
                                        echo '<span class="text-xs text-slate-500 italic">' . __('no_players_eliminated_yet') . '</span>';
                                    }
                                    ?>
                                </div>
                            <?php else: ?>
                                <p class="text-xs text-slate-400 font-bold">
                                    <?php 
                                    if ($lang === 'ku') {
                                        echo '🪦 گۆڕهەڵکەن بڕیاردا کو ڕۆلان ئاشکرا نەکەت.';
                                    } elseif ($lang === 'ar') {
                                        echo '🪦 قرر حارس القبور عدم كشف الأدوار.';
                                    } else {
                                        echo '🪦 Grave keeper decided not to reveal the roles.';
                                    }
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <p class="text-[11px] text-slate-500 italic"><?php echo __('read_phrase_notice'); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- JUDGE DAYTIME INFO -->
            <?php 
            $has_judge = false;
            $judge_name = '';
            $is_judge_alive = false;
            foreach ($db['players'] as $p) {
                if (($p['role'] ?? '') === 'Judge') {
                    $has_judge = true;
                    $judge_name = $p['name'];
                    if ($p['status'] === 'alive') {
                        $is_judge_alive = true;
                    }
                    break;
                }
            }
            ?>
            <?php if ($db['phase'] === 'day' && $has_judge && empty($db['winner'])): ?>
                <div class="bg-indigo-950/40 border border-indigo-900 p-5 rounded-xl space-y-4 shadow-lg">
                    <div class="flex items-center justify-between border-b border-indigo-900/60 pb-3">
                        <h3 class="text-sm font-black uppercase text-indigo-300 tracking-wider">
                            <?php echo __('judge_panel_title'); ?>
                        </h3>
                        <span class="bg-indigo-950/80 border border-indigo-800 text-indigo-300 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                            ⚖️ <?php echo get_role_label('Judge'); ?>
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="bg-slate-900 p-4 rounded-lg border border-slate-800 space-y-2">
                            <p class="text-xs text-slate-300 leading-relaxed">
                                ⚖️ <strong><?php echo __('role_judge'); ?>:</strong> <?php echo __('desc_judge'); ?>
                            </p>
                            <p class="text-[11px] text-slate-400">
                                <?php if ($is_judge_alive): ?>
                                    <span class="text-emerald-400 font-bold">● <?php echo get_current_lang() === 'ku' ? 'یاریزانێ دادوەر د ساخە:' : (get_current_lang() === 'ar' ? 'لاعب القاضي على قيد الحياة:' : 'The Judge player is alive:'); ?></span> 
                                    <strong class="text-white underline"><?php echo htmlspecialchars($judge_name); ?></strong>
                                <?php else: ?>
                                    <span class="text-rose-400 font-bold">○ <?php echo get_current_lang() === 'ku' ? 'دادوەر هاتیە کوشتن!' : (get_current_lang() === 'ar' ? 'تم قتل القاضي!' : 'The Judge has been eliminated!'); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="bg-indigo-950/20 border border-indigo-900/40 p-3.5 rounded-lg space-y-1 text-xs">
                            <p class="font-black text-indigo-300 uppercase tracking-wide">
                                <?php echo get_current_lang() === 'ku' ? 'ڕێنمایێن بڕیارێن دادوەری (بۆ مێهڤانداری):' : (get_current_lang() === 'ar' ? 'تعليمات قرارات القاضي (للمضيف):' : 'Judge Decision Guidelines (For Host):'); ?>
                            </p>
                            <ul class="list-disc list-inside text-slate-300 space-y-1.5 mt-2 leading-relaxed">
                                <li>
                                    <strong><?php echo get_current_lang() === 'ku' ? 'هەڵوەشاندنا هەمی دەنگدانان:' : (get_current_lang() === 'ar' ? 'إلغاء جميع الأصوات:' : 'Cancel All Votings:'); ?></strong> 
                                    <?php echo get_current_lang() === 'ku' ? 'مێهڤاندار دشێت هەمی دەنگدانان هەڵبوەشینیت و چ یاریزان ئەڤرۆ دەرنەکەڤن.' : (get_current_lang() === 'ar' ? 'يمكن للمضيف إلغاء جميع الأصوات لليوم حتى لا يغادر أحد.' : 'The host can manually cancel all votes for the day so no player is kicked.'); ?>
                                </li>
                                <li>
                                    <strong><?php echo get_current_lang() === 'ku' ? 'دەرئێخستنا یەک یاریزان تەنێ:' : (get_current_lang() === 'ar' ? 'طرد لاعب واحد فقط:' : 'Kick Only One Player:'); ?></strong> 
                                    <?php echo get_current_lang() === 'ku' ? 'دادوەر دشێت بڕیار بدەت کو بتنێ یاریزانەکێ دیاریکری دەرکەڤیت و یێن دی بهێنە پاراستن.' : (get_current_lang() === 'ar' ? 'يمكن للقاضي اختيار طرد لاعب محدد فقط من الذين تم التصويت عليهم، وإبقاء الآخرين.' : 'The judge can choose to kick exactly one voted player, saving everyone else.'); ?>
                                </li>
                            </ul>
                            <p class="text-[10px] text-slate-400 italic mt-2.5">
                                ℹ️ <?php echo get_current_lang() === 'ku' ? 'ژبەرکو بریارێن دەنگدانێ یێن دادوەری ب دەستی دهێنە کرن، مێهڤاندار دێ ب دەستی یاریزانان دەرکەتنا وان یان ژین د کۆنترۆلا یاریزانان دا گۆڕیت.' : (get_current_lang() === 'ar' ? 'بما أن قرارات القاضي والتصويت تتم يدوياً، سيقوم المضيف بتغيير حالة اللاعبين يدوياً في لوحة الإدارة.' : 'Since the Judge’s voting decisions are handled manually, the host will manually toggle player statuses in the player management table.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SUICIDAL BOMB REVENGE CONTROL -->
            <?php if ($db['phase'] === 'day' && !empty($db['suicidal_bomb_triggered_by']) && empty($db['winner'])): ?>
                <div class="bg-rose-950/80 border-2 border-rose-500/80 p-5 rounded-xl space-y-4 shadow-xl">
                    <div class="flex items-center justify-between border-b border-rose-900/80 pb-3">
                        <h3 class="text-sm font-black uppercase text-rose-300 tracking-wider flex items-center gap-2">
                            <span>💣</span> <?php echo __('suicidal_bomb_panel_title'); ?>
                        </h3>
                        <span class="bg-rose-900/80 border border-rose-700 text-rose-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                            💣 <?php echo get_role_label('Suicidal Bomb'); ?>: <?php echo htmlspecialchars($db['suicidal_bomb_triggered_by']); ?>
                        </span>
                    </div>

                    <form method="POST" class="bg-slate-900 p-4 rounded-lg border border-slate-800 space-y-3">
                        <input type="hidden" name="action" value="suicidal_bomb_explode">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-rose-200">
                                <?php echo __('suicidal_bomb_choose_target'); ?>
                            </label>
                            <select name="target_player_id" required class="w-full bg-slate-950 border border-rose-700/80 text-slate-200 text-xs rounded-lg p-2.5 focus:outline-none focus:border-rose-500">
                                <option value=""><?php echo __('make_selection'); ?></option>
                                <option value="none" class="text-emerald-400 font-bold"><?php echo __('suicidal_bomb_option_none'); ?></option>
                                <?php foreach ($db['players'] as $p): ?>
                                    <?php if ($p['status'] === 'alive'): ?>
                                        <option value="<?php echo htmlspecialchars($p['id']); ?>">
                                            <?php echo htmlspecialchars($p['name']); ?> (<?php echo htmlspecialchars(get_role_label($p['role'])); ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-2.5 rounded-lg uppercase tracking-wider transition shadow">
                                <?php echo __('suicidal_bomb_explode_btn'); ?>
                            </button>
                            <button type="submit" onclick="this.form.target_player_id.value='none';" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs py-2.5 rounded-lg uppercase tracking-wider transition shadow">
                                <?php echo __('suicidal_bomb_leave_alone_btn'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Host Control Panel -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Main Control Card -->
                <div class="md:col-span-2 bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-6 shadow-lg">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-bold text-slate-200"><?php echo __('lobby_and_players'); ?></h2>
                        <div class="text-xs font-bold bg-slate-800 px-3 py-1.5 rounded border border-slate-700 text-sky-400">
                            <?php echo __('connected_players'); ?> <span id="online-count"><?php echo count($db['players']); ?></span>
                        </div>
                    </div>

                    <!-- Grave Keeper Status Info Box -->
                    <?php if ($db['roles_shared'] ?? false): ?>
                        <div class="bg-slate-950 p-4 rounded-lg border border-indigo-900/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-indigo-400 uppercase block"><?php echo __('gk_status_reveal_state'); ?></span>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    <?php echo __('charges_left'); ?> <strong class="text-white"><?php echo ($db['grave_keeper_charges'] ?? 2); ?>/2</strong> | 
                                    <strong class="<?php echo ($db['grave_keeper_revealed_roles'] ?? false) ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                        <?php echo ($db['grave_keeper_revealed_roles'] ?? false) ? __('revealed_status_yes') : __('revealed_status_no'); ?>
                                    </strong>
                                </p>
                            </div>
                        </div>

                        <!-- Host Only Confidential Operations Log (Collapsible Details Block) -->
                        <?php if (!empty($db['last_night_report']['diary_entries']) || !empty($db['investigation_results'])): ?>
                            <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 space-y-2">
                                <details class="group">
                                    <summary class="flex justify-between items-center font-bold text-xs uppercase tracking-wider text-emerald-400 cursor-pointer select-none">
                                        <span class="flex items-center gap-1.5">
                                            <span>📝</span> <?php echo get_current_lang() === 'ku' ? 'یادداشتێن شەڤێ یێن پاراستی (نهێنی - تەنێ بۆ مێهڤانداری)' : (get_current_lang() === 'ar' ? 'سجل العمليات الليلي السري (للمضيف فقط)' : 'Confidential Night Operations Log (Host Only)'); ?>
                                        </span>
                                        <span class="text-slate-400 transition group-open:rotate-180">
                                            ▼
                                        </span>
                                    </summary>
                                    <div class="mt-3 border-t border-slate-800 pt-3 space-y-4">
                                        <!-- Investigator Results inside Confidential Log -->
                                        <?php if (!empty($db['investigation_results'])): ?>
                                            <div class="space-y-1.5">
                                                <p class="text-[11px] font-black uppercase text-sky-400 tracking-wider">
                                                    🔍 <?php echo __('investigator_result_last_night'); ?>
                                                </p>
                                                <div class="bg-slate-900 p-2.5 rounded border border-slate-800 space-y-1">
                                                    <?php foreach ($db['investigation_results'] as $res): ?>
                                                        <div class="text-xs text-slate-200">
                                                            <?php echo __('appeared_as', '<strong class="text-white">' . htmlspecialchars($res['target']) . '</strong>'); ?> 
                                                            <span class="px-2 py-0.5 rounded font-bold text-[10px] <?php echo $res['result'] === 'Mafia' ? 'bg-rose-950 text-rose-400' : 'bg-emerald-950 text-emerald-400'; ?>">
                                                                <?php echo $res['result'] === 'Mafia' ? get_role_label('Regular Mafia') : get_role_label('Citizen'); ?>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Diary Entries inside Confidential Log -->
                                        <?php if (!empty($db['last_night_report']['diary_entries'])): ?>
                                            <div class="space-y-1.5">
                                                <p class="text-[11px] font-black uppercase text-emerald-400 tracking-wider">
                                                    📊 <?php echo get_current_lang() === 'ku' ? 'کریارێن شەڤێ ب درێژی:' : (get_current_lang() === 'ar' ? 'تفاصيل الإجراءات الليلية:' : 'Detailed Night Action Log:'); ?>
                                                </p>
                                                <div class="bg-slate-900 p-3 rounded border border-slate-800 space-y-2 max-h-60 overflow-y-auto font-sans">
                                                    <?php foreach ($db['last_night_report']['diary_entries'] as $entry): 
                                                        $lang = get_current_lang();
                                                        $msg = $entry[$lang] ?? $entry['en'] ?? '';
                                                    ?>
                                                        <div class="text-xs text-slate-300 leading-relaxed border-b border-slate-950 pb-1.5 last:border-none last:pb-0">
                                                            <?php echo $msg; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Role Share Form -->
                    <div id="role-action-container">
                        <?php if (!($db['roles_shared'] ?? false)): ?>
                            <!-- Share Roles Button Trigger -->
                            <button type="button" onclick="document.getElementById('roles-config-modal').classList.remove('hidden')" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-lg font-bold text-xs uppercase tracking-wider transition shadow flex items-center justify-center gap-2">
                                <span>📦</span> <?php echo __('distribute_roles_start'); ?>
                            </button>

                            <!-- Add Bot Players for testing -->
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <form id="bot-form-1" class="w-full">
                                    <input type="hidden" name="action" value="add_bot">
                                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 border border-slate-700 text-sky-400 hover:text-sky-300 px-4 py-2.5 rounded-lg font-bold text-xs uppercase tracking-wider transition shadow flex items-center justify-center gap-2">
                                        <span>🤖</span> +1 Bot Player
                                    </button>
                                </form>
                                <form id="bot-form-5" class="w-full">
                                    <input type="hidden" name="action" value="add_five_bots">
                                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 border border-slate-700 text-sky-400 hover:text-sky-300 px-4 py-2.5 rounded-lg font-bold text-xs uppercase tracking-wider transition shadow flex items-center justify-center gap-2">
                                        <span>🤖🤖</span> +5 Bot Players
                                    </button>
                                </form>
                            </div>

                            <!-- Roles Selection Modal Overlay -->
                            <div id="roles-config-modal" class="hidden fixed inset-0 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                                <form method="POST" class="bg-slate-900 border border-slate-800 rounded-xl p-6 w-full max-w-md space-y-5 shadow-2xl relative">
                                    <input type="hidden" name="action" value="share_roles">
                                    
                                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                        <h3 class="text-sm font-black uppercase text-emerald-400 tracking-wider flex items-center gap-2">
                                            <span>⚙️</span> <?php echo __('distribute_roles_start'); ?>
                                        </h3>
                                        <button type="button" onclick="document.getElementById('roles-config-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-base transition font-bold">
                                            ✕
                                        </button>
                                    </div>

                                    <!-- Mafia Count Selection -->
                                    <div class="flex items-center justify-between bg-slate-950 p-3 rounded-lg border border-slate-800">
                                        <label for="mafia_count" class="text-xs text-slate-300 font-bold"><?php echo __('exact_mafia_count'); ?></label>
                                        <input type="number" id="mafia_count" name="mafia_count" value="2" min="1" max="15" 
                                               class="w-16 bg-slate-900 border border-slate-700 text-center text-white text-sm font-bold rounded p-1.5 focus:outline-none focus:border-rose-500">
                                    </div>

                                    <!-- Special Roles Inclusion Checkboxes -->
                                    <div class="space-y-2">
                                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">کۆنترۆلا ڕۆلێن یاریێ (تەخسیسکرن):</span>
                                        <div class="grid grid-cols-2 gap-2 text-xs text-slate-300 bg-slate-950 p-4 rounded-lg border border-slate-800 max-h-48 overflow-y-auto">
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Mafia Doctor" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Mafia Doctor'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Deceiver" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Deceiver'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Police" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Police'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Town Doctor" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Town Doctor'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Investigator" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Investigator'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Grave Keeper" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Grave Keeper'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Judge" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Judge'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Mirhas" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Mirhas'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Suicidal Bomb" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Suicidal Bomb'); ?></label>
                                        </div>
                                    </div>

                                    <!-- Explanation Note -->
                                    <div class="text-[11px] text-slate-400 bg-emerald-950/20 border border-emerald-900/60 p-3 rounded-lg leading-relaxed space-y-1">
                                        <p class="font-bold text-emerald-400">💡 تێبینی / Note:</p>
                                        <p>ژمارا وەلاتی و مافیایێن ئاسایی بێ سنوورە. ئەگەر تە بۆ نموونە ٣ مافیا هەڵبژارتن، ۱ دێ بیتە سەرۆکێ مافیایێ، یەک دێ بیتە دکتورێ مافیایێ (ئەگەر کارا بیت) و یێ دی دێ بیتە مافیایێ ئاسایی. هەمان یاسا بۆ وەلاتیێن ئاسایی ژی یا ب جهە.</p>
                                        <p class="text-slate-500 text-[10px]">Normal Citizens and normal Mafia are unlimited. They automatically fill any remaining player slots.</p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="grid grid-cols-2 gap-3 pt-2">
                                        <button type="button" onclick="document.getElementById('roles-config-modal').classList.add('hidden')" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs py-2.5 rounded-lg uppercase tracking-wider transition">
                                            <?php echo __('cancel'); ?>
                                        </button>
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 rounded-lg uppercase tracking-wider transition shadow">
                                            <?php echo __('confirm'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 text-center text-xs text-amber-400 font-bold uppercase tracking-wider">
                                <?php echo __('roles_distributed_hidden'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Players Table -->
                    <div class="overflow-x-auto border border-slate-800 rounded-lg">
                        <table class="w-full text-left rtl:text-right border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-950 text-slate-400 text-xs uppercase border-b border-slate-800">
                                    <th class="p-3"><?php echo __('player_name'); ?></th>
                                    <th class="p-3"><?php echo __('assigned_role'); ?></th>
                                    <th class="p-3"><?php echo __('status'); ?></th>
                                    <th class="p-3 text-right rtl:text-left"><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="players-table-body" class="divide-y divide-slate-800">
                                <?php if (empty($db['players'])): ?>
                                    <tr><td colspan="4" class="p-4 text-center text-slate-500 text-xs"><?php echo __('no_players_registered'); ?></td></tr>
                                <?php else: ?>
                                    <?php foreach ($db['players'] as $p): ?>
                                        <tr class="hover:bg-slate-800/50">
                                            <td class="p-3 font-semibold"><?php echo htmlspecialchars($p['name']); ?></td>
                                            <td class="p-3">
                                                <span class="px-2.5 py-1 rounded text-xs font-bold bg-slate-800 text-sky-300 border border-slate-700">
                                                    <?php echo htmlspecialchars(get_role_label($p['role'])); ?>
                                                </span>
                                            </td>
                                            <td class="p-3">
                                                <span class="text-xs font-bold <?php echo $p['status'] === 'alive' ? 'text-emerald-400' : 'text-rose-500 line-through'; ?>">
                                                    <?php 
                                                        if (in_array($p['name'], $db['delayed_departure'] ?? [])) {
                                                            echo __('alive_temporarily_mirhas');
                                                        } else {
                                                            echo $p['status'] === 'alive' ? __('alive') : __('dead');
                                                        }
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="p-3 text-right rtl:text-left space-x-2 flex flex-wrap justify-end gap-1">
                                                <?php if ($db['phase'] === 'day' && $p['status'] === 'alive' && !in_array($p['name'], $db['delayed_departure'] ?? []) && empty($db['winner'])): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="kick_player_day">
                                                        <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                                                        <button type="submit" class="text-xs text-amber-300 hover:text-white bg-amber-950/60 px-2.5 py-1 rounded border border-amber-800">
                                                            <?php echo __('vote_kick_daytime'); ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                 <?php if (!($db['roles_shared'] ?? false)): ?>
                                                     <form method="POST" class="inline">
                                                         <input type="hidden" name="action" value="remove_player_setup">
                                                         <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                                                         <button type="submit" class="text-xs text-rose-400 hover:text-white bg-rose-950/60 px-2.5 py-1 rounded border border-rose-900/60 transition">
                                                             <?php echo get_current_lang() === 'ku' ? 'ژێبرن' : (get_current_lang() === 'ar' ? 'حذف' : 'Remove'); ?>
                                                         </button>
                                                     </form>
                                                 <?php endif; ?>
                                                 <?php if ($db['roles_shared'] ?? false): ?>
                                                     <form method="POST" class="inline">
                                                         <input type="hidden" name="action" value="toggle_status">
                                                         <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                                                         <button type="submit" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2.5 py-1 rounded border border-slate-700">
                                                             <?php echo __('toggle_alive_dead'); ?>
                                                         </button>
                                                     </form>
                                                 <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Game Phase Sidebar -->
                <div class="space-y-6">
                    <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-4 shadow-lg">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400"><?php echo __('phase_management'); ?></h2>
                        <div class="bg-slate-950 border border-slate-800 p-4 rounded-lg text-center space-y-2">
                            <span class="text-xs text-slate-500 uppercase block"><?php echo __('current_phase'); ?></span>
                            <div class="text-xl font-black uppercase text-rose-400" id="phase-label">
                                <?php echo $db['winner'] !== null ? __('game_over') : (__('phase_' . $db['phase']) . ' ' . ($db['phase'] !== 'setup' ? $db['day'] : '')); ?>
                            </div>
                        </div>

                        <?php if (empty($db['winner'])): ?>
                            <?php if ($db['roles_shared'] ?? false): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="next_phase">
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 py-3 rounded font-bold text-xs uppercase tracking-wider shadow transition">
                                        <?php echo __('go_to_next_phase'); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="bg-amber-950/40 border border-amber-900/60 p-3 rounded-lg text-center text-xs text-amber-300 font-bold uppercase">
                                    <?php echo __('unlock_phase_notice'); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <script>
                const i18nRoles = <?php echo json_encode($role_i18n_map); ?>;
                const i18nTxt = {
                    noPlayers: <?php echo json_encode(__('no_players_registered')); ?>,
                    aliveTemp: <?php echo json_encode(__('alive_temporarily_mirhas')); ?>,
                    alive: <?php echo json_encode(__('alive')); ?>,
                    dead: <?php echo json_encode(__('dead')); ?>,
                    voteKick: <?php echo json_encode(__('vote_kick_daytime')); ?>,
                    toggle: <?php echo json_encode(__('toggle_alive_dead')); ?>,
                    gameOver: <?php echo json_encode(__('game_over')); ?>,
                    phaseSetup: <?php echo json_encode(__('phase_setup')); ?>,
                    phaseNight: <?php echo json_encode(__('phase_night')); ?>,
                    phaseDay: <?php echo json_encode(__('phase_day')); ?>,
                    recorded: <?php echo json_encode(__('recorded')); ?>,
                    pending: <?php echo json_encode(__('pending')); ?>,
                    decided: <?php echo json_encode(__('decided')); ?>,
                    gkDecisionRecorded: <?php echo json_encode(__('gk_decision_recorded')); ?>,
                    selectedPrefix: <?php echo json_encode(__('selected')); ?>
                };

                let lastRolesSharedState = <?php echo json_encode($db['roles_shared'] ?? false); ?>;
                
                // --- AUDIO SYNTHESIZER AND SOUND SYSTEM ---
                let AudioContextClass = window.AudioContext || window.webkitAudioContext;
                let audioCtx = null;

                function getAudioContext() {
                    if (!audioCtx && AudioContextClass) {
                        audioCtx = new AudioContextClass();
                    }
                    return audioCtx;
                }

                // Robust unlocker for browsers that suspend or block audio
                function unlockAudioContext() {
                    let ctx = getAudioContext();
                    if (ctx) {
                        if (ctx.state === 'suspended') {
                            ctx.resume().then(() => {
                                console.log("AudioContext resumed successfully via gesture.");
                            }).catch(err => {
                                console.warn("Could not resume AudioContext:", err);
                            });
                        }
                    }
                    document.removeEventListener('click', unlockAudioContext);
                    document.removeEventListener('touchstart', unlockAudioContext);
                }
                document.addEventListener('click', unlockAudioContext);
                document.addEventListener('touchstart', unlockAudioContext);

                function playSound(type) {
                    if (localStorage.getItem('mafia_sound_muted') === 'true') return;
                    
                    let ctx = getAudioContext();
                    if (!ctx) return;

                    // Resume context if browser suspended it (required for security/user gesture policies)
                    if (ctx.state === 'suspended') {
                        ctx.resume();
                    }

                    const now = ctx.currentTime;
                    
                    try {
                        switch (type) {
                            case 'tick':
                                // Soft mechanical tick for low-time
                                {
                                    const osc = ctx.createOscillator();
                                    const gain = ctx.createGain();
                                    osc.type = 'triangle';
                                    osc.frequency.setValueAtTime(350, now);
                                    gain.gain.setValueAtTime(0.04, now);
                                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);
                                    osc.connect(gain);
                                    gain.connect(ctx.destination);
                                    osc.start(now);
                                    osc.stop(now + 0.05);
                                }
                                break;
                                
                            case 'alarm':
                                // Repeating ringing alarm
                                for (let i = 0; i < 4; i++) {
                                    const time = now + (i * 0.25);
                                    const osc = ctx.createOscillator();
                                    const gain = ctx.createGain();
                                    osc.type = 'square';
                                    osc.frequency.setValueAtTime(880, time);
                                    osc.frequency.setValueAtTime(1760, time + 0.1);
                                    gain.gain.setValueAtTime(0.1, time);
                                    gain.gain.exponentialRampToValueAtTime(0.001, time + 0.2);
                                    osc.connect(gain);
                                    gain.connect(ctx.destination);
                                    osc.start(time);
                                    osc.stop(time + 0.2);
                                }
                                break;
                                
                            case 'daybreak':
                                // Majestic morning chime
                                {
                                    const notes = [261.63, 329.63, 392.00, 523.25]; // C4, E4, G4, C5
                                    notes.forEach((freq, idx) => {
                                        const osc = ctx.createOscillator();
                                        const gain = ctx.createGain();
                                        osc.type = 'sine';
                                        osc.frequency.setValueAtTime(freq, now + idx * 0.12);
                                        gain.gain.setValueAtTime(0.08, now + idx * 0.12);
                                        gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.12 + 0.5);
                                        osc.connect(gain);
                                        gain.connect(ctx.destination);
                                        osc.start(now + idx * 0.12);
                                        osc.stop(now + idx * 0.12 + 0.6);
                                    });
                                }
                                break;
                                
                            case 'nightfall':
                                // Mysterious ambient low tones
                                {
                                    const notes = [164.81, 146.83, 110.00]; // E3, D3, A2
                                    notes.forEach((freq, idx) => {
                                        const osc = ctx.createOscillator();
                                        const gain = ctx.createGain();
                                        osc.type = 'sine';
                                        osc.frequency.setValueAtTime(freq, now + idx * 0.18);
                                        gain.gain.setValueAtTime(0.1, now + idx * 0.18);
                                        gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.18 + 0.7);
                                        osc.connect(gain);
                                        gain.connect(ctx.destination);
                                        osc.start(now + idx * 0.18);
                                        osc.stop(now + idx * 0.18 + 0.8);
                                    });
                                }
                                break;
                                
                            case 'click':
                                // Subtle mechanical interface click
                                {
                                    const osc = ctx.createOscillator();
                                    const gain = ctx.createGain();
                                    osc.type = 'sine';
                                    osc.frequency.setValueAtTime(600, now);
                                    osc.frequency.exponentialRampToValueAtTime(200, now + 0.03);
                                    gain.gain.setValueAtTime(0.03, now);
                                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.03);
                                    osc.connect(gain);
                                    gain.connect(ctx.destination);
                                    osc.start(now);
                                    osc.stop(now + 0.04);
                                }
                                break;
                        }
                    } catch (e) {
                        console.warn("Audio playback failed: ", e);
                    }
                }

                function toggleMute() {
                    const isMuted = localStorage.getItem('mafia_sound_muted') === 'true';
                    const newMuted = !isMuted;
                    localStorage.setItem('mafia_sound_muted', newMuted ? 'true' : 'false');
                    updateMuteUI();
                    
                    // Activate Context on first toggle to bypass browser autoplays blocking
                    getAudioContext();
                    if (!newMuted) {
                        playSound('click');
                    }
                }

                function updateMuteUI() {
                    const isMuted = localStorage.getItem('mafia_sound_muted') === 'true';
                    const muteIcon = document.getElementById('mute-icon');
                    const muteText = document.getElementById('mute-text');
                    const isKu = "<?php echo get_current_lang(); ?>" === "ku";
                    const isAr = "<?php echo get_current_lang(); ?>" === "ar";
                    
                    if (isMuted) {
                        if (muteIcon) muteIcon.innerText = '🔇';
                        if (muteText) muteText.innerText = isKu ? 'دەنگ: بڕاو' : (isAr ? 'الصوت: مكتوم' : 'Sounds: OFF');
                    } else {
                        if (muteIcon) muteIcon.innerText = '🔊';
                        if (muteText) muteText.innerText = isKu ? 'دەنگ: کارا' : (isAr ? 'الصوت: مفعّل' : 'Sounds: ON');
                    }
                }

                // --- COUNTDOWN TIMER ENGINE ---
                let timerInterval = null;
                let defaultDuration = 120; // 2 minutes default

                function getRemainingSeconds() {
                    let saved = localStorage.getItem('mafia_timer_seconds');
                    if (saved === null) return defaultDuration;
                    return parseInt(saved, 10);
                }

                function saveRemainingSeconds(sec) {
                    localStorage.setItem('mafia_timer_seconds', sec);
                }

                function updateTimerDisplay() {
                    let totalSeconds = getRemainingSeconds();
                    let mins = Math.floor(totalSeconds / 60);
                    let secs = totalSeconds % 60;
                    
                    let displayStr = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
                    const displayElem = document.getElementById('timer-display');
                    if (displayElem) {
                        displayElem.innerText = displayStr;
                        
                        // Visual cues
                        if (totalSeconds === 0) {
                            displayElem.className = "text-2xl font-black font-mono tracking-wider text-rose-500 animate-pulse";
                        } else if (totalSeconds <= 10) {
                            displayElem.className = "text-2xl font-black font-mono tracking-wider text-amber-500";
                        } else {
                            displayElem.className = "text-2xl font-black font-mono tracking-wider text-indigo-400";
                        }
                    }
                }

                function toggleTimer() {
                    playSound('click');
                    let isRunning = localStorage.getItem('mafia_timer_running') === 'true';
                    if (isRunning) {
                        pauseTimer();
                    } else {
                        startTimer();
                    }
                }

                function startTimer() {
                    localStorage.setItem('mafia_timer_running', 'true');
                    localStorage.setItem('mafia_timer_last_saved_time', Date.now());
                    
                    const playBtn = document.getElementById('timer-play-btn');
                    if (playBtn) playBtn.innerText = '⏸️';

                    if (timerInterval) clearInterval(timerInterval);
                    timerInterval = setInterval(() => {
                        let sec = getRemainingSeconds();
                        if (sec > 0) {
                            sec--;
                            saveRemainingSeconds(sec);
                            updateTimerDisplay();
                            localStorage.setItem('mafia_timer_last_saved_time', Date.now());
                            
                            // Play tick sounds in last 10 seconds
                            if (sec <= 10 && sec > 0) {
                                playSound('tick');
                            }
                            
                            if (sec === 0) {
                                pauseTimer();
                                playSound('alarm');
                            }
                        } else {
                            pauseTimer();
                        }
                    }, 1000);
                }

                function pauseTimer() {
                    localStorage.setItem('mafia_timer_running', 'false');
                    const playBtn = document.getElementById('timer-play-btn');
                    if (playBtn) playBtn.innerText = '▶️';
                    if (timerInterval) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                    }
                }

                function resetTimer() {
                    playSound('click');
                    pauseTimer();
                    saveRemainingSeconds(defaultDuration);
                    updateTimerDisplay();
                }

                function setTimerPreset(sec) {
                    playSound('click');
                    pauseTimer();
                    defaultDuration = sec;
                    saveRemainingSeconds(sec);
                    updateTimerDisplay();
                }

                // Handle background/tab recovery
                function checkAndRecoverTimer() {
                    let isRunning = localStorage.getItem('mafia_timer_running') === 'true';
                    if (isRunning) {
                        let lastSaved = parseInt(localStorage.getItem('mafia_timer_last_saved_time') || '0', 10);
                        if (lastSaved > 0) {
                            let elapsed = Math.floor((Date.now() - lastSaved) / 1000);
                            if (elapsed > 0) {
                                let sec = getRemainingSeconds();
                                let newSec = Math.max(0, sec - elapsed);
                                saveRemainingSeconds(newSec);
                                if (newSec === 0) {
                                    localStorage.setItem('mafia_timer_running', 'false');
                                    playSound('alarm');
                                }
                            }
                        }
                        startTimer();
                    } else {
                        updateTimerDisplay();
                    }
                }

                // Auto-hook click events for standard buttons for tactile feedback
                document.addEventListener('click', (e) => {
                    const tag = e.target.tagName.toLowerCase();
                    if (tag === 'button' || (tag === 'input' && e.target.type === 'submit') || e.target.closest('a')) {
                        // Exclude mute and timer buttons to avoid double clicking sounds
                        if (e.target.id !== 'mute-btn' && !e.target.closest('#mute-btn') && e.target.id !== 'timer-play-btn' && !e.target.closest('#timer-play-btn') && !e.target.closest('[onclick^="setTimerPreset"]') && !e.target.closest('[onclick^="resetTimer"]')) {
                            playSound('click');
                        }
                    }
                });

                // ON LOAD BOOTSTRAP FOR AUDIO AND TIMER
                document.addEventListener('DOMContentLoaded', () => {
                    updateMuteUI();
                    checkAndRecoverTimer();

                    // Phase Change Sound Alerts
                    const currentPhase = "<?php echo $db['phase']; ?>";
                    const savedPhase = sessionStorage.getItem('mafia_last_phase');
                    if (savedPhase && savedPhase !== currentPhase) {
                        if (currentPhase === 'day') {
                            playSound('daybreak');
                        } else if (currentPhase === 'night') {
                            playSound('nightfall');
                        }
                    }
                    sessionStorage.setItem('mafia_last_phase', currentPhase);
                });
                let lastPhaseState = "<?php echo $db['phase']; ?>";
                let lastWinnerState = <?php echo json_encode($db['winner'] ?? null); ?>;
                let lastGraveRevealState = <?php echo json_encode($db['grave_keeper_revealed_roles'] ?? false); ?>;
                let lastGraveChargesState = <?php echo json_encode($db['grave_keeper_charges'] ?? 2); ?>;

                let lastNightActionsState = <?php echo json_encode($db['night_actions'] ?? []); ?>;

                function getPhaseLabel(phase, day, winner) {
                    if (winner) return i18nTxt.gameOver;
                    let pName = phase === 'setup' ? i18nTxt.phaseSetup : (phase === 'night' ? i18nTxt.phaseNight : i18nTxt.phaseDay);
                    return pName + (phase !== 'setup' ? ' ' + day : '');
                }

                function pollHost() {
                    fetch('actions.php?ajax=1')
                        .then(r => r.json())
                        .then(data => {
                            if (data.roles_shared !== lastRolesSharedState || data.phase !== lastPhaseState || data.winner !== lastWinnerState || data.grave_keeper_revealed_roles !== lastGraveRevealState || data.grave_keeper_charges !== lastGraveChargesState) {
                                location.reload();
                                return;
                            }

                            if (JSON.stringify(data.night_actions) !== JSON.stringify(lastNightActionsState)) {
                                lastNightActionsState = data.night_actions;
                                if (typeof refreshNightCardUI === 'function') {
                                    const allRoles = ['Mafia', 'Mafia Doctor', 'Deceiver', 'Regular Mafia', 'Police', 'Town Doctor', 'Investigator', 'Judge', 'Grave Keeper', 'Mirhas', 'Citizen'];
                                    allRoles.forEach(r => refreshNightCardUI(r, data));
                                }
                            }

                            document.getElementById('online-count').innerText = data.players.length;
                            document.getElementById('phase-label').innerText = getPhaseLabel(data.phase, data.day, data.winner);

                            const tbody = document.getElementById('players-table-body');
                            if (data.players.length === 0) {
                                tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-slate-500 text-xs">${i18nTxt.noPlayers}</td></tr>`;
                            } else {
                                let html = '';
                                data.players.forEach(p => {
                                    let isDelayed = (data.delayed_departure || []).includes(p.name);
                                    let statusText = isDelayed ? i18nTxt.aliveTemp : (p.status === 'alive' ? i18nTxt.alive : i18nTxt.dead);
                                    let statusClass = (p.status === 'alive' || isDelayed) ? 'text-emerald-400' : 'text-rose-500 line-through';

                                    let kickButton = '';
                                    if (data.phase === 'day' && p.status === 'alive' && !isDelayed && !data.winner) {
                                        kickButton = `
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="kick_player_day">
                                                <input type="hidden" name="player_id" value="${p.id}">
                                                <button type="submit" class="text-xs text-amber-300 hover:text-white bg-amber-950/60 px-2.5 py-1 rounded border border-amber-800">
                                                    ${i18nTxt.voteKick}
                                                </button>
                                            </form>
                                        `;
                                    }

                                    let setupRemoveButton = "";
                                    if (!data.roles_shared) {
                                        let removeLabel = "<?php echo get_current_lang() === 'ku' ? 'ژێبرن' : (get_current_lang() === 'ar' ? 'حذف' : 'Remove'); ?>";
                                        setupRemoveButton = `
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="remove_player_setup">
                                                <input type="hidden" name="player_id" value="${p.id}">
                                                <button type="submit" class="text-xs text-rose-400 hover:text-white bg-rose-950/60 px-2.5 py-1 rounded border border-rose-900/60 transition">
                                                    ${removeLabel}
                                                </button>
                                            </form>
                                        `;
                                    }
                                    let roleTranslated = i18nRoles[p.role] || p.role;

                                    html += `
                                        <tr class="hover:bg-slate-800/50">
                                            <td class="p-3 font-semibold">${p.name}</td>
                                            <td class="p-3"><span class="px-2.5 py-1 rounded text-xs font-bold bg-slate-800 text-sky-300 border border-slate-700">${roleTranslated}</span></td>
                                            <td class="p-3"><span class="text-xs font-bold ${statusClass}">${statusText}</span></td>
                                            <td class="p-3 text-right rtl:text-left space-x-2 flex flex-wrap justify-end gap-1">
                                                ${kickButton}
                                                ${setupRemoveButton}
                                                ${data.roles_shared ? `
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="player_id" value="${p.id}">
                                                    <button type="submit" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2.5 py-1 rounded border border-slate-700">
                                                        ${i18nTxt.toggle}
                                                    </button>
                                                </form>
                                                ` : ""}
                                            </td>
                                        </tr>
                                    `;
                                });
                                tbody.innerHTML = html;
                            }

                            const logsContainer = document.getElementById('logs-container');
                            let logsHtml = '';
                            [...data.logs].reverse().forEach(log => {
                                logsHtml += `<div class="border-b border-slate-900 pb-1">${log}</div>`;
                            });
                            logsContainer.innerHTML = logsHtml;
                        });
                }

                setInterval(pollHost, 2000);

                // AJAX handler for night actions without full page reload
                function handleNightActionSubmit(event, role) {
                    event.preventDefault();
                    const form = event.target;
                    const formData = new FormData(form);
                    formData.append('ajax', '1');

                    fetch('actions.php?ajax=1', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        lastNightActionsState = data.night_actions;
                        if (typeof refreshNightCardUI === 'function') {
                            const allRoles = ['Mafia', 'Mafia Doctor', 'Deceiver', 'Regular Mafia', 'Police', 'Town Doctor', 'Investigator', 'Judge', 'Grave Keeper', 'Mirhas', 'Citizen'];
                            allRoles.forEach(r => refreshNightCardUI(r, data));
                        }
                        if (typeof pollHost === 'function') {
                            pollHost();
                        }
                    })
                    .catch(err => console.error('Error submitting night action:', err));
                }

                function cancelNightAction(role) {
                    const formData = new FormData();
                    formData.append('action', 'record_night_target');
                    formData.append('role', role);
                    formData.append('target_id', '');
                    formData.append('ajax', '1');

                    fetch('actions.php?ajax=1', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        lastNightActionsState = data.night_actions;
                        if (typeof refreshNightCardUI === 'function') {
                            const allRoles = ['Mafia', 'Mafia Doctor', 'Deceiver', 'Regular Mafia', 'Police', 'Town Doctor', 'Investigator', 'Judge', 'Grave Keeper', 'Mirhas', 'Citizen'];
                            allRoles.forEach(r => refreshNightCardUI(r, data));
                        }
                        if (typeof pollHost === 'function') {
                            pollHost();
                        }
                    })
                    .catch(err => console.error('Error canceling night action:', err));
                }

                function refreshNightCardUI(role, data) {
                    const card = document.querySelector(`[data-role-card="${role}"]`);
                    if (!card) return;

                    const statusBadge = card.querySelector('.status-badge');

                    if (role === 'Grave Keeper') {
                        // Handle Grave Keeper state lock after decision
                        const gkButtons = card.querySelector('#gk-buttons-container');
                        if (gkButtons) gkButtons.classList.add('hidden');
                        if (statusBadge) {
                            statusBadge.className = "status-badge text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800 px-2 py-0.5 rounded font-bold uppercase";
                            statusBadge.innerText = i18nTxt.decided;
                        }
                        const formElem = card.querySelector('form');
                        if (formElem && !formElem.querySelector('.text-emerald-400')) {
                            const notice = document.createElement('div');
                            notice.className = "text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center";
                            notice.innerText = i18nTxt.gkDecisionRecorded;
                            formElem.appendChild(notice);
                        }
                    } else {
                        const recordedTarget = data.night_actions ? data.night_actions[role] : null;
                        const cancelBtn = card.querySelector('.cancel-btn');
                        const resultContainer = card.querySelector('.result-container');
                        const selectedText = card.querySelector('.selected-text');
                        const selectElem = card.querySelector('.target-select');

                        if (recordedTarget) {
                            if (statusBadge) {
                                statusBadge.className = "status-badge text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800 px-2 py-0.5 rounded font-bold uppercase";
                                statusBadge.innerText = i18nTxt.recorded;
                            }
                            if (cancelBtn) cancelBtn.classList.remove('hidden');
                            if (resultContainer) resultContainer.classList.remove('hidden');
                            if (selectedText) selectedText.innerText = i18nTxt.selectedPrefix + " " + recordedTarget;
                        } else {
                            if (statusBadge) {
                                statusBadge.className = "status-badge text-[10px] bg-amber-950 text-amber-400 border border-amber-800 px-2 py-0.5 rounded font-bold uppercase";
                                statusBadge.innerText = i18nTxt.pending;
                            }
                            if (selectElem) selectElem.value = "";
                            if (cancelBtn) cancelBtn.classList.add('hidden');
                            if (resultContainer) resultContainer.classList.add('hidden');
                        }
                    }
                }

                // Helper to get cookie value
                function getCookie(name) {
                    let value = "; " + document.cookie;
                    let parts = value.split("; " + name + "=");
                    if (parts.length === 2) return parts.pop().split(";").shift();
                }

                // Intercept all POST form submissions on index.php to avoid page reload
                document.addEventListener('submit', function(e) {
                    const form = e.target;
                    if (form.tagName === 'FORM' && form.method.toLowerCase() === 'post') {
                        if (e.defaultPrevented) return;
                        e.preventDefault();

                        const formData = new FormData(form);
                        formData.append('ajax', '1');

                        let targetUrl = form.getAttribute('action') || '';
                        if (!targetUrl) {
                            targetUrl = window.location.pathname;
                        }

                        fetch(targetUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            const browserId = getCookie('mafia_browser_id');
                            const isHostPage = window.location.pathname.endsWith('index.php') || window.location.pathname === '/';
                            
                            // Redirect from host to player if they are no longer host
                            if (isHostPage && data.host_browser_id && data.host_browser_id !== browserId) {
                                window.location.href = 'player.php';
                                return;
                            }

                            // If we are in the host page, trigger pollHost() to instantly update UI
                            if (typeof pollHost === 'function') {
                                pollHost();
                            }

                            // Hide the Roles selection modal if it is open
                            const rolesModal = document.getElementById('roles-config-modal');
                            if (rolesModal && !rolesModal.classList.contains('hidden')) {
                                rolesModal.classList.add('hidden');
                            }
                        })
                        .catch(err => {
                            console.error('AJAX form submission error:', err);
                            window.location.reload();
                        });
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
