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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_title_player'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 md:p-6 font-sans flex flex-col items-center justify-center space-y-4">
    
    <!-- Language Selector & Navigation Header Bar -->
    <div class="max-w-md w-full flex flex-col sm:flex-row justify-between items-center bg-slate-800 border border-slate-700 p-3 rounded-xl shadow-lg gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-400 font-bold uppercase tracking-wider">
            🌐 <?php echo __('language'); ?>:
            <?php render_language_selector(); ?>
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

                function startLocalCountdown(roleName) {
                    if (localStorage.getItem('mafia_role_revealed_' + myPlayerId) === 'true') {
                        renderHiddenState();
                        return;
                    }

                    if (window.isCountingDown) return;
                    window.isCountingDown = true;

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

                // --- INITIAL LOAD LOGIC ---
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
