<?php
require_once 'actions.php';

if (!$is_host && !$needs_host_claim) {
    header("Location: player.php");
    exit;
}

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
    'Citizen'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Control Panel - Mafia Game</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6 font-sans">
    
    <div class="max-w-6xl mx-auto space-y-6">

        <!-- BIG VICTORY BANNER (If Game Over) -->
        <?php if (!empty($db['winner'])): ?>
            <div class="bg-gradient-to-r <?php echo $db['winner'] === 'Citizens' ? 'from-emerald-900 via-teal-900 to-emerald-950 border-emerald-500 text-emerald-300' : 'from-rose-900 via-red-950 to-rose-950 border-rose-500 text-rose-300'; ?> border-4 p-8 rounded-2xl shadow-2xl text-center space-y-4 animate-pulse">
                <div class="text-5xl">🏆</div>
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-wider text-white drop-shadow-lg">
                    <?php echo $db['winner']; ?> Win!
                </h1>
                <p class="text-sm md:text-base font-semibold text-slate-200">
                    <?php if ($db['winner'] === 'Citizens'): ?>
                        All Mafia members have been successfully eliminated or justice has prevailed!
                    <?php else: ?>
                        The Mafia have successfully infiltrated and outnumbered the town!
                    <?php endif; ?>
                </p>
                <div class="pt-2">
                    <form method="POST">
                        <input type="hidden" name="action" value="hide_roles">
                        <button type="submit" class="bg-white text-slate-950 hover:bg-slate-200 px-8 py-3 rounded-xl font-black text-sm uppercase tracking-widest shadow-2xl transition transform hover:scale-105">
                            🔄 Start New Rematch / Reset
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Header -->
        <header class="bg-slate-900 border border-slate-800 p-6 rounded-xl flex flex-col sm:flex-row justify-between items-center gap-4 shadow-xl">
            <div>
                <h1 class="text-2xl font-black text-rose-500 uppercase tracking-wider">🔪 Mafia Host Control Panel</h1>
                <p class="text-xs text-slate-400 mt-1">Manage game roles, track night actions, and automate phase transitions.</p>
            </div>
            
            <?php if ($needs_host_claim): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="claim_host">
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 px-6 py-2.5 rounded-lg font-bold text-xs uppercase tracking-wider shadow transition">
                        Claim Host Role
                    </button>
                </form>
            <?php else: ?>
                <div class="flex items-center gap-3 flex-wrap justify-end">
                    <?php if ($db['roles_shared'] ?? false): ?>
                        <form method="POST" onsubmit="return confirm('Do you want to start a new Rematch? Names will be kept, roles and history will be reset.');">
                            <input type="hidden" name="action" value="hide_roles">
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wider shadow transition">
                                🔄 Rematch
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return confirm('Are you sure you want to completely reset the session and clear players?');">
                        <input type="hidden" name="action" value="hard_reset">
                        <button type="submit" class="text-xs text-rose-400 hover:underline bg-slate-800 px-3 py-1.5 rounded border border-slate-700">
                            Reset Session
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </header>

        <?php if (!$needs_host_claim && $is_host): ?>
            
            <!-- NIGHT PHASE GUIDED ASSISTANT -->
            <?php if ($db['phase'] === 'night' && empty($db['winner'])): ?>
                <div class="bg-indigo-950/60 border-2 border-indigo-500/60 p-6 rounded-xl space-y-5 shadow-2xl">
                    <div class="flex justify-between items-center border-b border-indigo-900/80 pb-3">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-widest text-indigo-400">🌙 Night Control Center</span>
                            <h2 class="text-xl font-black text-white mt-0.5">Call Roles & Record Night Actions</h2>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="next_phase">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded font-bold text-xs uppercase tracking-wider shadow transition">
                                End Night ➔ Start Day
                            </button>
                        </form>
                    </div>

                    <p class="text-xs text-amber-300 bg-amber-950/40 border border-amber-900/60 p-3 rounded-lg">
                        ⚠️ <strong>Host Calling Rule:</strong> 
                        <?php if ($db['grave_keeper_revealed_roles'] ?? false): ?>
                            Because Grave Keeper agreed to reveal roles, you can <strong class="text-emerald-400">skip calling eliminated roles</strong>!
                        <?php else: ?>
                            Until Grave Keeper agrees to reveal roles, you must <strong class="text-rose-400">call all active/inactive roles</strong> so players cannot guess who died!
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

                        $call_grave_keeper_tonight = ($has_grave_keeper && $gk_charges > 0 && !$gk_revealed);

                        foreach ($all_game_roles as $role): 
                            if (in_array($role, ['Judge', 'Citizen', 'Mirhas'])) continue;
                            
                            if ($role === 'Grave Keeper') {
                                if (!$call_grave_keeper_tonight) continue; 
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
                                                echo "Grave Keeper (Charges left: $gk_charges/2)";
                                            } else {
                                                echo $role;
                                            }
                                            ?>
                                        </span>
                                        <?php if ($role === 'Grave Keeper'): ?>
                                            <span class="status-badge text-[10px] <?php echo $gk_acted_tonight ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-indigo-950 text-indigo-400 border border-indigo-800'; ?> px-2 py-0.5 rounded font-bold uppercase">
                                                <?php echo $gk_acted_tonight ? 'Decided' : 'Host Prompt'; ?>
                                            </span>
                                        <?php else: ?>
                                            <?php 
                                            $recorded_target = $db['night_actions'][$role] ?? null;
                                            ?>
                                            <span class="status-badge text-[10px] px-2 py-0.5 rounded font-bold uppercase <?php echo $recorded_target ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-amber-950 text-amber-400 border border-amber-800'; ?>">
                                                <?php echo $recorded_target ? 'Recorded' : 'Pending'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <?php 
                                        if ($role === 'Grave Keeper') {
                                            echo $gk_acted_tonight ? "Grave Keeper has already made a decision for tonight." : "Select Grave Keeper's action for tonight:";
                                        } elseif ($role === 'Mafia Boss') {
                                            echo "Select target for Mafia to eliminate tonight:";
                                        } elseif ($role === 'Mafia Doctor') {
                                            echo "Select fellow Mafia member to protect:";
                                        } elseif ($role === 'Police') {
                                            echo "Select player to shoot (cannot select self):";
                                        } elseif ($role === 'Town Doctor') {
                                            echo "Select player to protect (Self-heals used: " . ($db['town_doctor_self_protect_count'] ?? 0) . "/2):";
                                        } elseif ($role === 'Investigator') {
                                            echo "Select player to investigate (cannot select self):";
                                        } else {
                                            echo "Select target:";
                                        }
                                        ?>
                                    </p>
                                </div>

                                <form onsubmit="handleNightActionSubmit(event, '<?php echo $role; ?>')" class="space-y-2">
                                    <?php if ($role === 'Grave Keeper'): ?>
                                        <input type="hidden" name="action" value="answer_grave_keeper_reveal">
                                        <div class="space-y-2 <?php echo $gk_acted_tonight ? 'hidden' : ''; ?>" id="gk-buttons-container">
                                            <select name="reveal_answer" class="w-full bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded p-2 focus:outline-none focus:border-rose-500">
                                                <option value="">-- Make a Selection --</option>
                                                <option value="yes">نعم - Reveal eliminated roles (Yes)</option>
                                                <option value="no">لا - Do not reveal (No)</option>
                                            </select>
                                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 rounded uppercase tracking-wider transition shadow">
                                                Confirm Decision
                                            </button>
                                        </div>
                                        <?php if ($gk_acted_tonight): ?>
                                            <div class="text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center">
                                                Decision recorded for tonight.
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="record_night_target">
                                        <input type="hidden" name="role" value="<?php echo $role; ?>">
                                        <select name="target_id" class="target-select w-full bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded p-2 focus:outline-none focus:border-rose-500">
                                            <option value="">-- None / No Selection --</option>
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
                                                Confirm
                                            </button>
                                            <button type="button" onclick="cancelNightAction('<?php echo $role; ?>')" class="cancel-btn bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-800 font-bold text-xs px-3 py-2 rounded uppercase tracking-wider transition <?php echo $recorded_target ? '' : 'hidden'; ?>" title="Cancel Selection">
                                                ✕ Cancel
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </form>

                                <?php if ($role !== 'Grave Keeper'): ?>
                                    <?php $recorded_target = $db['night_actions'][$role] ?? null; ?>
                                    <div class="result-container space-y-1 <?php echo $recorded_target ? '' : 'hidden'; ?>">
                                        <div class="selected-text text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center truncate">
                                            Selected: <?php echo htmlspecialchars($recorded_target ?? ''); ?>
                                        </div>

                                        <?php if ($role === 'Investigator'): 
                                            $eval_res = $recorded_target ? evaluate_investigation($recorded_target, $db) : '';
                                        ?>
                                            <div class="investigator-result text-xs font-bold p-2 rounded border text-center <?php echo $eval_res === 'Mafia' ? 'bg-rose-950/80 border-rose-800 text-rose-300 animate-pulse' : 'bg-sky-950/80 border-sky-800 text-sky-300'; ?>">
                                                🔍 Investigator Result: <span class="underline uppercase"><?php echo $eval_res; ?></span>
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
                <div class="bg-slate-900 border-2 border-rose-505/50 border-rose-500/50 p-6 rounded-xl space-y-3 shadow-2xl">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">☀️</span>
                        <h2 class="text-lg font-black uppercase text-rose-400">Day <?php echo $db['day']; ?> Morning Report (Host Only)</h2>
                    </div>
                    
                    <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 space-y-3 text-sm">
                        <?php if (empty($killed_list)): ?>
                            <p class="text-emerald-400 font-bold text-base">
                                ✨ No players are leaving the game today.
                            </p>
                        <?php else: ?>
                            <?php foreach ($killed_list as $kname): ?>
                                <p class="text-rose-400 font-black text-base">
                                    ⚠️ <span class="text-white underline"><?php echo htmlspecialchars($kname); ?> is leaving game</span>
                                </p>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Grave Keeper Morning Section Added Here -->
                        <div class="border-t border-slate-800 pt-3 mt-3 space-y-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-indigo-400">🪦 Grave Keeper Decision Status:</p>
                            <?php if ($db['grave_keeper_revealed_roles'] ?? false): ?>
                                <p class="text-xs text-emerald-400 font-bold">
                                    Decision: <span class="underline">YES (Reveal Roles)</span>
                                </p>
                                <div class="mt-2 bg-indigo-950/40 border border-indigo-900 p-3 rounded space-y-1">
                                    <span class="text-[11px] text-slate-400 uppercase font-bold block">Roles of players currently out of the game (dead):</span>
                                    <?php 
                                    $dead_roles_found = false;
                                    foreach ($db['players'] as $pl) {
                                        if ($pl['status'] === 'dead' || in_array($pl['name'], $db['delayed_departure'] ?? [])) {
                                            $dead_roles_found = true;
                                            echo '<div class="text-xs text-rose-300 font-bold">• ' . htmlspecialchars($pl['name']) . ' was a <span class="text-white uppercase underline">' . htmlspecialchars($pl['role']) . '</span></div>';
                                        }
                                    }
                                    if (!$dead_roles_found) {
                                        echo '<div class="text-xs text-slate-500 italic">No players have been eliminated yet.</div>';
                                    }
                                    ?>
                                </div>
                            <?php else: ?>
                                <p class="text-xs text-slate-400 font-bold">
                                    Decision: <span class="text-amber-400">NO / None</span> (Roles remain hidden)
                                </p>
                            <?php endif; ?>
                        </div>

                        <p class="text-[11px] text-slate-500 italic">Read this phrase to the players without disclosing backend details.</p>
                    </div>

                    <?php if (!empty($db['investigation_results'])): ?>
                        <div class="bg-sky-950/40 border border-sky-900 p-4 rounded-lg space-y-2">
                            <h3 class="text-xs font-bold text-sky-400 uppercase">Investigator Result Recorded Last Night:</h3>
                            <?php foreach ($db['investigation_results'] as $res): ?>
                                <div class="text-xs text-slate-200">
                                    Player <strong class="text-white"><?php echo htmlspecialchars($res['target']); ?></strong> appeared to the investigator as: 
                                    <span class="px-2 py-0.5 rounded font-bold <?php echo $res['result'] === 'Mafia' ? 'bg-rose-950 text-rose-400' : 'bg-emerald-950 text-emerald-400'; ?>">
                                        <?php echo $res['result'] === 'Mafia' ? 'Mafia' : 'Regular Citizen'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Host Control Panel -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Main Control Card -->
                <div class="md:col-span-2 bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-6 shadow-lg">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-bold text-slate-200">Lobby & Players</h2>
                        <div class="text-xs font-bold bg-slate-800 px-3 py-1.5 rounded border border-slate-700 text-sky-400">
                            Connected Players: <span id="online-count"><?php echo count($db['players']); ?></span>
                        </div>
                    </div>

                    <!-- Grave Keeper Status Info Box -->
                    <?php if ($db['roles_shared'] ?? false): ?>
                        <div class="bg-slate-950 p-4 rounded-lg border border-indigo-900/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-indigo-400 uppercase block">Grave Keeper Status & Reveal State</span>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Charges Left: <strong class="text-white"><?php echo ($db['grave_keeper_charges'] ?? 2); ?>/2</strong> | 
                                    Roles Revealed Status: <strong class="<?php echo ($db['grave_keeper_revealed_roles'] ?? false) ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                        <?php echo ($db['grave_keeper_revealed_roles'] ?? false) ? 'Revealed (Skip dead roles at night)' : 'Hidden (Call all roles at night)'; ?>
                                    </strong>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Role Share Form -->
                    <div id="role-action-container">
                        <?php if (!($db['roles_shared'] ?? false)): ?>
                            <form method="POST" class="bg-slate-950 p-4 rounded-lg border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                                <input type="hidden" name="action" value="share_roles">
                                
                                <div class="flex items-center gap-3">
                                    <label for="mafia_count" class="text-xs text-slate-300 font-bold">Exact Mafia Count:</label>
                                    <input type="number" id="mafia_count" name="mafia_count" value="2" min="1" max="15" 
                                           class="w-16 bg-slate-900 border border-slate-700 text-center text-white text-sm font-bold rounded p-1.5 focus:outline-none focus:border-rose-500">
                                </div>

                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 px-5 py-2.5 rounded font-bold text-xs uppercase tracking-wider transition shadow">
                                    Distribute Roles & Start Game
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 text-center text-xs text-amber-400 font-bold uppercase tracking-wider">
                                🔒 Roles successfully distributed and hidden from players.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Players Table -->
                    <div class="overflow-x-auto border border-slate-800 rounded-lg">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-950 text-slate-400 text-xs uppercase border-b border-slate-800">
                                    <th class="p-3">Player Name</th>
                                    <th class="p-3">Assigned Role</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="players-table-body" class="divide-y divide-slate-800">
                                <?php if (empty($db['players'])): ?>
                                    <tr><td colspan="4" class="p-4 text-center text-slate-500 text-xs">No players registered yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($db['players'] as $p): ?>
                                        <tr class="hover:bg-slate-800/50">
                                            <td class="p-3 font-semibold"><?php echo htmlspecialchars($p['name']); ?></td>
                                            <td class="p-3">
                                                <span class="px-2.5 py-1 rounded text-xs font-bold bg-slate-800 text-sky-300 border border-slate-700">
                                                    <?php echo htmlspecialchars($p['role']); ?>
                                                </span>
                                            </td>
                                            <td class="p-3">
                                                <span class="text-xs font-bold <?php echo $p['status'] === 'alive' ? 'text-emerald-400' : 'text-rose-500 line-through'; ?>">
                                                    <?php 
                                                        if (in_array($p['name'], $db['delayed_departure'] ?? [])) {
                                                            echo 'Alive Temporarily (Mirhas)';
                                                        } else {
                                                            echo $p['status'] === 'alive' ? 'Alive' : 'Dead';
                                                        }
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="p-3 text-right space-x-2 flex flex-wrap justify-end gap-1">
                                                <?php if ($db['phase'] === 'day' && $p['status'] === 'alive' && !in_array($p['name'], $db['delayed_departure'] ?? []) && empty($db['winner'])): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="kick_player_day">
                                                        <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                                                        <button type="submit" class="text-xs text-amber-300 hover:text-white bg-amber-950/60 px-2.5 py-1 rounded border border-amber-800">
                                                            Vote / Kick Daytime
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2.5 py-1 rounded border border-slate-700">
                                                        Toggle Alive/Dead
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Game Phase & Logs Sidebar -->
                <div class="space-y-6">
                    <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-4 shadow-lg">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Phase Management</h2>
                        <div class="bg-slate-950 border border-slate-800 p-4 rounded-lg text-center space-y-2">
                            <span class="text-xs text-slate-500 uppercase block">Current Phase</span>
                            <div class="text-xl font-black uppercase text-rose-400" id="phase-label">
                                <?php echo $db['winner'] !== null ? 'GAME OVER' : ($db['phase'] . ' ' . ($db['phase'] !== 'setup' ? $db['day'] : '')); ?>
                            </div>
                        </div>

                        <?php if (empty($db['winner'])): ?>
                            <?php if ($db['roles_shared'] ?? false): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="next_phase">
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 py-3 rounded font-bold text-xs uppercase tracking-wider shadow transition">
                                        Go to Next Phase ➔
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="bg-amber-950/40 border border-amber-900/60 p-3 rounded-lg text-center text-xs text-amber-300 font-bold uppercase">
                                    ⚠️ Distribute roles above to unlock phase transitions
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Activity Log -->
                    <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-3 shadow-lg">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Activity Log</h2>
                        <div class="bg-slate-950 border border-slate-800 p-3 rounded-lg h-48 overflow-y-auto text-xs space-y-2 font-mono text-slate-300 text-left" id="logs-container">
                            <?php foreach (array_reverse($db['logs']) as $log): ?>
                                <div class="border-b border-slate-900 pb-1"><?php echo htmlspecialchars($log); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

            <script>
                let lastRolesSharedState = <?php echo json_encode($db['roles_shared'] ?? false); ?>;
                let lastPhaseState = "<?php echo $db['phase']; ?>";
                let lastWinnerState = <?php echo json_encode($db['winner'] ?? null); ?>;
                let lastGraveRevealState = <?php echo json_encode($db['grave_keeper_revealed_roles'] ?? false); ?>;
                let lastGraveChargesState = <?php echo json_encode($db['grave_keeper_charges'] ?? 2); ?>;

                function pollHost() {
                    fetch('actions.php?ajax=1')
                        .then(r => r.json())
                        .then(data => {
                            if (data.roles_shared !== lastRolesSharedState || data.phase !== lastPhaseState || data.winner !== lastWinnerState || data.grave_keeper_revealed_roles !== lastGraveRevealState || data.grave_keeper_charges !== lastGraveChargesState) {
                                location.reload();
                                return;
                            }

                            document.getElementById('online-count').innerText = data.players.length;
                            document.getElementById('phase-label').innerText = data.winner ? 'GAME OVER' : (data.phase.toUpperCase() + (data.phase !== 'setup' ? ' ' + data.day : ''));

                            const tbody = document.getElementById('players-table-body');
                            if (data.players.length === 0) {
                                tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-slate-500 text-xs">No players registered yet.</td></tr>`;
                            } else {
                                let html = '';
                                data.players.forEach(p => {
                                    let isDelayed = (data.delayed_departure || []).includes(p.name);
                                    let statusText = isDelayed ? 'Alive Temporarily (Mirhas)' : (p.status === 'alive' ? 'Alive' : 'Dead');
                                    let statusClass = (p.status === 'alive' || isDelayed) ? 'text-emerald-400' : 'text-rose-500 line-through';

                                    let kickButton = '';
                                    if (data.phase === 'day' && p.status === 'alive' && !isDelayed && !data.winner) {
                                        kickButton = `
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="kick_player_day">
                                                <input type="hidden" name="player_id" value="${p.id}">
                                                <button type="submit" class="text-xs text-amber-300 hover:text-white bg-amber-950/60 px-2.5 py-1 rounded border border-amber-800">
                                                    Vote / Kick Daytime
                                                </button>
                                            </form>
                                        `;
                                    }

                                    html += `
                                        <tr class="hover:bg-slate-800/50">
                                            <td class="p-3 font-semibold">${p.name}</td>
                                            <td class="p-3"><span class="px-2.5 py-1 rounded text-xs font-bold bg-slate-800 text-sky-300 border border-slate-700">${p.role}</span></td>
                                            <td class="p-3"><span class="text-xs font-bold ${statusClass}">${statusText}</span></td>
                                            <td class="p-3 text-right space-x-2 flex flex-wrap justify-end gap-1">
                                                ${kickButton}
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="player_id" value="${p.id}">
                                                    <button type="submit" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2.5 py-1 rounded border border-slate-700">
                                                        Toggle Alive/Dead
                                                    </button>
                                                </form>
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

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(() => {
                        fetch('actions.php?ajax=1')
                            .then(r => r.json())
                            .then(data => {
                                refreshNightCardUI(role, data);
                            });
                    })
                    .catch(err => console.error('Error submitting night action:', err));
                }

                function cancelNightAction(role) {
                    const formData = new FormData();
                    formData.append('action', 'record_night_target');
                    formData.append('role', role);
                    formData.append('target_id', '');

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(() => {
                        fetch('actions.php?ajax=1')
                            .then(r => r.json())
                            .then(data => {
                                refreshNightCardUI(role, data);
                            });
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
                            statusBadge.innerText = "Decided";
                        }
                        const formElem = card.querySelector('form');
                        if (formElem && !formElem.querySelector('.text-emerald-400')) {
                            const notice = document.createElement('div');
                            notice.className = "text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center";
                            notice.innerText = "Decision recorded for tonight.";
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
                                statusBadge.innerText = "Recorded";
                            }
                            if (cancelBtn) cancelBtn.classList.remove('hidden');
                            if (resultContainer) resultContainer.classList.remove('hidden');
                            if (selectedText) selectedText.innerText = "Selected: " + recordedTarget;
                        } else {
                            if (statusBadge) {
                                statusBadge.className = "status-badge text-[10px] bg-amber-950 text-amber-400 border border-amber-800 px-2 py-0.5 rounded font-bold uppercase";
                                statusBadge.innerText = "Pending";
                            }
                            if (selectElem) selectElem.value = "";
                            if (cancelBtn) cancelBtn.classList.add('hidden');
                            if (resultContainer) resultContainer.classList.add('hidden');
                        }
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
