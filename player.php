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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mafia - Player Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen p-6 font-sans flex items-center justify-center">
    
    <div class="max-w-md w-full bg-slate-800 border border-slate-700 p-8 rounded-xl text-center space-y-6 shadow-2xl">
        <h1 class="text-2xl font-black text-sky-400 uppercase">👥 Player Portal</h1>
        
        <?php if (!empty($_SESSION['join_error'])): ?>
            <div class="bg-rose-500/20 border border-rose-500 text-rose-300 text-xs p-3 rounded font-bold">
                <?php echo htmlspecialchars($_SESSION['join_error']); unset($_SESSION['join_error']); ?>
            </div>
        <?php endif; ?>

        <?php if (!$my_player): ?>
            <p class="text-xs text-slate-400">Enter your name to join the lobby.</p>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="join_game">
                <input type="text" name="player_name" placeholder="Your Display Name..." required
                       class="bg-slate-900 border border-slate-700 rounded px-4 py-3 text-sm w-full text-center font-bold focus:outline-none focus:border-sky-500">
                <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 py-3 rounded font-bold uppercase text-xs tracking-wider shadow">
                    Join Game
                </button>
            </form>
        <?php else: ?>
            <div class="bg-slate-900 p-6 rounded-lg border border-slate-700 space-y-4">
                <span class="text-xs text-slate-400 uppercase block font-bold">Logged in as: <span class="text-amber-400 text-sm font-black tracking-wide"><?php echo htmlspecialchars($my_player['name']); ?></span></span>
                
                <div class="py-6 border-y border-slate-700 min-h-[140px] flex flex-col justify-center items-center" id="role-container">
                    <div class="text-sm font-bold text-amber-400 animate-pulse flex flex-col items-center gap-2">
                        <span>⏳</span>Waiting for host to share roles...
                    </div>
                </div>

                <div class="text-xs text-slate-400">Current Phase: <strong id="phase-display" class="uppercase text-white"><?php echo htmlspecialchars($db['phase']); ?></strong></div>
            </div>

            <script>
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
                            <span class="text-xs text-slate-500 uppercase block mb-2">Role Hidden</span>
                            <div class="text-sm font-bold text-slate-600">Your role is hidden to prevent peeking.</div>
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

                    countdownInterval = setInterval(() => {
                        if (timeLeft > 0) {
                            if (container) {
                                container.innerHTML = `
                                    <span class="text-xs text-slate-400 uppercase block mb-2">Your Secret Role</span>
                                    <div class="text-3xl font-black text-rose-400 uppercase">${roleName}</div>
                                    <div class="text-xs text-amber-400 mt-4 font-bold">Hiding in ${timeLeft}s...</div>
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
                                phaseDisplay.innerText = dbData.phase.toUpperCase() + (dbData.phase !== 'setup' ? ' ' + dbData.day : '');
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
                // Rule: Stop continuous hits.
                // 1. If roles are already shared on load: show role/hidden state and DO NOT poll!
                // 2. If registered and waiting: poll until roles are shared, then stop!
                // 3. If unregistered: DO NOT poll!
                if (initialRolesShared && initialMyRole && initialMyRole !== 'Pending') {
                    // Roles are ALREADY shared when page loads.
                    // Display the role and DO NOT request anything from the server!
                    startLocalCountdown(initialMyRole);
                } else if (myPlayerId) {
                    // User is registered and waiting for host to share roles.
                    // Poll every 3 seconds until roles are shared, then stop immediately.
                    pollTimer = setInterval(pollPlayer, 3000);
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
