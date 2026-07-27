<?php
require_once 'actions.php';

if ($is_host) {
    header("Location: index.php");
    exit;
}

$my_player = null;
$player_id = $_SESSION['player_id'] ?? null;

if ($player_id) {
    foreach ($db['players'] as $p) {
        if ($p['id'] === $player_id) {
            $my_player = $p;
            break;
        }
    }
}

// Build role map for JS translation
$all_game_roles = [
    'Mafia',
    'Mafia Boss',
    'Mafia Doctor',
    'Deceiver',
    'Regular Mafia',
    'Police',
    'Town Doctor',
    'Investigator',
    'Judge',
    'Grave Keeper',
    'Mirhas',
    'Suicidal Bomb',
    'Citizen',
    'Pending'
];

$role_i18n_map = [];
foreach ($all_game_roles as $r_name) {
    $role_i18n_map[$r_name] = get_role_label($r_name);
}
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
    <title><?php echo __('app_title_player'); ?></title>
    <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/tailwind.js?v=2"></script>
    <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/qrcode.min.js?v=2"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 md:p-6 font-sans flex flex-col items-center justify-center space-y-4">
    
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
    <div class="max-w-md w-full bg-slate-800 border border-slate-700 p-4 rounded-xl shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="text-3xl">📡</div>
            <div>
                <h2 class="text-sm font-bold text-slate-200">Connect to Game</h2>
                <p class="text-xs text-slate-400 font-mono mt-1">Domain: <span class="text-indigo-400"><?php echo htmlspecialchars($host); ?></span></p>
                <p class="text-xs text-slate-400 font-mono">IP: <span class="text-indigo-400"><?php echo htmlspecialchars($ip); ?></span></p>
            </div>
        </div>
        <div>
            <button onclick="document.getElementById('qr-modal-player').classList.toggle('hidden'); if(!window.qrCreated){ new QRCode(document.getElementById('qrcode-container-player'), '<?php echo addslashes($join_url); ?>'); window.qrCreated=true; }" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2">
                <span>📱</span> QR Code
            </button>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qr-modal-player" class="hidden flex justify-center max-w-md w-full mb-2">
        <div class="bg-white p-4 rounded-xl shadow-xl flex flex-col items-center w-full max-w-xs mx-auto">
            <div id="qrcode-container-player" class="mb-2"></div>
            <p class="text-xs font-bold text-slate-800 uppercase tracking-widest text-center">Scan to Join</p>
        </div>
    </div>

    <!-- Language Selector & Navigation Header Bar -->
    <div class="max-w-md w-full flex flex-col sm:flex-row justify-between items-center bg-slate-800 border border-slate-700 p-3 rounded-xl shadow-lg gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 text-xs text-slate-400 font-bold uppercase tracking-wider">
                🌐 <?php echo __('language'); ?>:
                <?php render_language_selector(); ?>
            </div>
            <!-- Sound Mute Button -->
            <button type="button" id="mute-btn" onclick="toggleMute()" class="bg-slate-700 hover:bg-slate-650 text-slate-300 border border-slate-600 rounded-lg px-2.5 py-1 text-[10px] font-black transition flex items-center gap-1 shadow-sm">
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

    <div class="max-w-md w-full bg-slate-800 border border-slate-700 p-8 rounded-xl text-center space-y-6 shadow-2xl">
        <h1 class="text-2xl font-black text-sky-400 uppercase"><?php echo __('player_portal_title'); ?></h1>
        
        <?php if (empty($db['host_browser_id'])): ?>
            <!-- No Host Error Banner -->
            <div class="bg-rose-950/80 border-2 border-rose-800 p-6 rounded-xl space-y-4 shadow-xl">
                <div class="text-3xl">🛑</div>
                <div class="space-y-2">
                    <h2 class="text-lg font-black text-rose-400 uppercase tracking-wider">
                        <?php echo __('no_host_error_title'); ?>
                    </h2>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        <?php echo __('no_host_error_desc'); ?>
                    </p>
                </div>
                <a href="index.php" class="inline-block w-full bg-rose-600 hover:bg-rose-700 text-white font-black py-3 rounded-xl text-xs uppercase tracking-widest shadow transition">
                    <?php echo __('claim_host_now'); ?>
                </a>
            </div>
        <?php else: ?>

        <?php if (!empty($_SESSION['join_error'])): ?>
            <div class="bg-rose-500/20 border border-rose-500 text-rose-300 text-xs p-3 rounded font-bold">
                <?php echo htmlspecialchars($_SESSION['join_error']); unset($_SESSION['join_error']); ?>
            </div>
        <?php endif; ?>

        <?php if (!$my_player): ?>
            <p class="text-xs text-slate-400"><?php echo __('enter_name_to_join'); ?></p>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="join_game">
                <input type="text" name="player_name" placeholder="<?php echo htmlspecialchars(__('your_display_name'), ENT_QUOTES); ?>" required
                       class="bg-slate-900 border border-slate-700 rounded px-4 py-3 text-sm w-full text-center font-bold focus:outline-none focus:border-sky-500">
                <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 py-3 rounded font-bold uppercase text-xs tracking-wider shadow">
                    <?php echo __('join_game'); ?>
                </button>
            </form>
        <?php else: ?>
            <div class="bg-slate-900 p-6 rounded-lg border border-slate-700 space-y-4">
                <span class="text-xs text-slate-400 uppercase block font-bold"><?php echo __('logged_in_as'); ?> <span class="text-amber-400 text-sm font-black tracking-wide"><?php echo htmlspecialchars($my_player['name']); ?></span></span>
                
                <div class="py-6 border-y border-slate-700 min-h-[140px] flex flex-col justify-center items-center" id="role-container">
                    <div class="text-sm font-bold text-amber-400 animate-pulse flex flex-col items-center gap-2">
                        <span>⏳</span><?php echo __('waiting_for_host'); ?>
                    </div>
                </div>

                <div class="text-xs text-slate-400"><?php echo __('current_phase'); ?>: <strong id="phase-display" class="uppercase text-white"><?php echo htmlspecialchars(__('phase_' . $db['phase']) . ($db['phase'] !== 'setup' ? ' ' . $db['day'] : '')); ?></strong></div>
            </div>

            <script>
                const i18nRoles = <?php echo json_encode($role_i18n_map); ?>;
                const i18nTxt = {
                    roleHiddenTitle: <?php echo json_encode(__('role_hidden_title')); ?>,
                    roleHiddenDesc: <?php echo json_encode(__('role_hidden_desc')); ?>,
                    secretRole: <?php echo json_encode(__('secret_role')); ?>,
                    hidingInSec: <?php echo json_encode(__('hiding_in_seconds')); ?>,
                    phaseSetup: <?php echo json_encode(__('phase_setup')); ?>,
                    phaseNight: <?php echo json_encode(__('phase_night')); ?>,
                    phaseDay: <?php echo json_encode(__('phase_day')); ?>
                };

                const myPlayerId = "<?php echo $my_player ? $my_player['id'] : ''; ?>";
                const initialRolesShared = <?php echo ($db['roles_shared'] ?? false) ? 'true' : 'false'; ?>;
                const initialMyRole = "<?php echo htmlspecialchars($my_player['role'] ?? 'Pending'); ?>";
                let lastResetToken = "<?php echo $db['reset_token'] ?? ''; ?>";
                let pollTimer = null;
                let countdownInterval = null;

                // Sync local storage reset token
                if (localStorage.getItem('mafia_reset_token') !== lastResetToken) {
                    localStorage.removeItem('mafia_role_revealed_' + myPlayerId);
                    localStorage.setItem('mafia_reset_token', lastResetToken);
                }

                function renderHiddenState() {
                    const container = document.getElementById('role-container');
                    if (container) {
                        container.innerHTML = `
                            <span class="text-xs text-slate-500 uppercase block mb-2">${i18nTxt.roleHiddenTitle}</span>
                            <div class="text-sm font-bold text-slate-600">${i18nTxt.roleHiddenDesc}</div>
                        `;
                    }
                }

                let isAudioPlayedOnReveal = false;
                function startLocalCountdown(roleName) {
                    if (localStorage.getItem('mafia_role_revealed_' + myPlayerId) === 'true') {
                        renderHiddenState();
                        return;
                    }

                    if (window.isCountingDown) return;
                    window.isCountingDown = true;

                    // Play reveal alarm sound to alert player!
                    if (!isAudioPlayedOnReveal) {
                        isAudioPlayedOnReveal = true;
                        playSound('alarm');
                    }

                    let timeLeft = 5;
                    const container = document.getElementById('role-container');
                    const translatedRole = i18nRoles[roleName] || roleName;

                    countdownInterval = setInterval(() => {
                        if (timeLeft > 0) {
                            if (container) {
                                let hideText = i18nTxt.hidingInSec.replace('%d', timeLeft);
                                container.innerHTML = `
                                    <span class="text-xs text-slate-400 uppercase block mb-2">${i18nTxt.secretRole}</span>
                                    <div class="text-3xl font-black text-rose-400 uppercase">${translatedRole}</div>
                                    <div class="text-xs text-amber-400 mt-4 font-bold">${hideText}</div>
                                `;
                            }
                            timeLeft--;
                        } else {
                            clearInterval(countdownInterval);
                            localStorage.setItem('mafia_role_revealed_' + myPlayerId, 'true');
                            renderHiddenState();
                        }
                    }, 1000);
                }

                function stopPolling() {
                    if (pollTimer) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                    }
                }

                function pollPlayer() {
                    fetch('actions.php?ajax=1')
                        .then(r => r.json())
                        .then(dbData => {
                            // Check if host reset the game session
                            if (dbData.reset_token && dbData.reset_token !== lastResetToken) {
                                lastResetToken = dbData.reset_token;
                                localStorage.setItem('mafia_reset_token', lastResetToken);
                                localStorage.removeItem('mafia_role_revealed_' + myPlayerId);
                                stopPolling();
                                window.location.reload();
                                return;
                            }

                            const phaseDisplay = document.getElementById('phase-display');
                            if (phaseDisplay) {
                                let pName = dbData.phase === 'setup' ? i18nTxt.phaseSetup : (dbData.phase === 'night' ? i18nTxt.phaseNight : i18nTxt.phaseDay);
                                phaseDisplay.innerText = pName + (dbData.phase !== 'setup' ? ' ' + dbData.day : '');
                            }

                            const currentPlayer = dbData.players ? dbData.players.find(p => p.id === myPlayerId) : null;

                            if (currentPlayer && dbData.roles_shared && currentPlayer.role && currentPlayer.role !== 'Pending') {
                                // Roles are now shared! Reveal the role and STOP POLLING immediately!
                                startLocalCountdown(currentPlayer.role);
                                stopPolling(); // STOP ALL SERVER REQUESTS
                            }
                        })
                        .catch(err => {
                            console.error("Polling error:", err);
                        });
                }

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

                    if (ctx.state === 'suspended') {
                        ctx.resume();
                    }

                    const now = ctx.currentTime;
                    
                    try {
                        switch (type) {
                            case 'alarm':
                                // Loud ringing alarm bell effect when timer expires
                                for (let i = 0; i < 16; i++) {
                                    const time = now + (i * 0.12);
                                    const osc = ctx.createOscillator();
                                    const gain = ctx.createGain();
                                    osc.type = 'sawtooth';
                                    osc.frequency.setValueAtTime(i % 2 === 0 ? 1046.50 : 1318.51, time); // C6 & E6
                                    gain.gain.setValueAtTime(0.35, time);
                                    gain.gain.exponentialRampToValueAtTime(0.001, time + 0.11);
                                    osc.connect(gain);
                                    gain.connect(ctx.destination);
                                    osc.start(time);
                                    osc.stop(time + 0.11);
                                }
                                break;
                                
                            case 'click':
                                // Click sound disabled per user request
                                break;
                        }
                    } catch (e) {
                        console.warn("Audio failed: ", e);
                    }
                }

                function toggleMute() {
                    const isMuted = localStorage.getItem('mafia_sound_muted') === 'true';
                    const newMuted = !isMuted;
                    localStorage.setItem('mafia_sound_muted', newMuted ? 'true' : 'false');
                    updateMuteUI();
                    
                    getAudioContext();
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

                // --- INITIAL LOAD LOGIC ---
                document.addEventListener('DOMContentLoaded', () => {
                    updateMuteUI();
                });

                // Helper to get cookie value
                function getCookie(name) {
                    let value = "; " + document.cookie;
                    let parts = value.split("; " + name + "=");
                    if (parts.length === 2) return parts.pop().split(";").shift();
                }

                // Intercept all POST form submissions on player.php to avoid page reload
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
                            const isPlayerPage = window.location.pathname.endsWith('player.php');
                            
                            // Redirect from player to host if they successfully claimed host
                            if (isPlayerPage && data.host_browser_id && data.host_browser_id === browserId) {
                                window.location.href = 'index.php';
                                return;
                            }

                            // If they registered successfully (join_game), reload to render logged in template
                            const actionInput = form.querySelector('input[name="action"]');
                            if (actionInput && actionInput.value === 'join_game') {
                                window.location.reload();
                                return;
                            }

                            // If they submitted a reset or similar action, reload
                            if (actionInput && (actionInput.value === 'hard_reset' || actionInput.value === 'reset_session')) {
                                window.location.reload();
                                return;
                            }

                            // Otherwise, update the player state dynamically!
                            if (typeof pollPlayer === 'function') {
                                pollPlayer();
                            }
                        })
                        .catch(err => {
                            console.error('AJAX form submission error:', err);
                            window.location.reload();
                        });
                    }
                });

                if (initialRolesShared && initialMyRole && initialMyRole !== 'Pending') {
                    startLocalCountdown(initialMyRole);
                } else if (myPlayerId) {
                    pollTimer = setInterval(pollPlayer, 3000);
                }
            </script>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
