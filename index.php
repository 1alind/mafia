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

$role_i18n_map = [];
foreach ($all_game_roles as $r_name) {
    $role_i18n_map[$r_name] = get_role_label($r_name);
}
$role_i18n_map['Pending'] = get_role_label('Pending');
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_lang(); ?>" dir="<?php echo get_current_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_title_host'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 md:p-6 font-sans">
    
    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Language Selector Header Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-center bg-slate-900 border border-slate-800 p-3 rounded-xl shadow-lg gap-3">
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
                                        } elseif ($role === 'Mafia Boss') {
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
                                                <option value="yes"><?php echo __('gk_option_yes'); ?></option>
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
                    
                    <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 space-y-3 text-sm">
                        <?php if (empty($killed_list)): ?>
                            <p class="text-emerald-400 font-bold text-base">
                                <?php echo __('no_players_leaving'); ?>
                            </p>
                        <?php else: ?>
                            <?php foreach ($killed_list as $kname): ?>
                                <p class="text-rose-400 font-black text-base">
                                    <?php echo __('player_leaving_game', '<span class="text-white underline">' . htmlspecialchars($kname) . '</span>'); ?>
                                </p>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Grave Keeper Morning Section -->
                        <div class="border-t border-slate-800 pt-3 mt-3 space-y-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-indigo-400"><?php echo __('gk_decision_status'); ?></p>
                            <?php if ($db['grave_keeper_revealed_roles'] ?? false): ?>
                                <p class="text-xs text-emerald-400 font-bold">
                                    <?php echo __('gk_decision_yes'); ?>
                                </p>
                                <div class="mt-2 bg-indigo-950/40 border border-indigo-900 p-3 rounded space-y-1">
                                    <span class="text-[11px] text-slate-400 uppercase font-bold block"><?php echo __('roles_out_of_game'); ?></span>
                                    <?php 
                                    $dead_roles_found = false;
                                    foreach ($db['players'] as $pl) {
                                        if ($pl['status'] === 'dead' || in_array($pl['name'], $db['delayed_departure'] ?? [])) {
                                            $dead_roles_found = true;
                                            echo '<div class="text-xs text-rose-300 font-bold">• ' . htmlspecialchars($pl['name']) . ' <span class="text-white uppercase underline">' . htmlspecialchars(get_role_label($pl['role'])) . '</span></div>';
                                        }
                                    }
                                    if (!$dead_roles_found) {
                                        echo '<div class="text-xs text-slate-500 italic">' . __('no_players_eliminated_yet') . '</div>';
                                    }
                                    ?>
                                </div>
                            <?php else: ?>
                                <p class="text-xs text-slate-400 font-bold">
                                    <?php echo __('gk_decision_no'); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <p class="text-[11px] text-slate-500 italic"><?php echo __('read_phrase_notice'); ?></p>
                    </div>

                    <?php if (!empty($db['investigation_results'])): ?>
                        <div class="bg-sky-950/40 border border-sky-900 p-4 rounded-lg space-y-2">
                            <h3 class="text-xs font-bold text-sky-400 uppercase"><?php echo __('investigator_result_last_night'); ?></h3>
                            <?php foreach ($db['investigation_results'] as $res): ?>
                                <div class="text-xs text-slate-200">
                                    <?php echo __('appeared_as', '<strong class="text-white">' . htmlspecialchars($res['target']) . '</strong>'); ?> 
                                    <span class="px-2 py-0.5 rounded font-bold <?php echo $res['result'] === 'Mafia' ? 'bg-rose-950 text-rose-400' : 'bg-emerald-950 text-emerald-400'; ?>">
                                        <?php echo $res['result'] === 'Mafia' ? get_role_label('Regular Mafia') : get_role_label('Citizen'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- JUDGE DAYTIME CONTROLS -->
            <?php if ($db['phase'] === 'day' && empty($db['winner'])): ?>
                <div class="bg-indigo-950/60 border-2 border-indigo-500/60 p-5 rounded-xl space-y-4 shadow-xl">
                    <div class="flex items-center justify-between border-b border-indigo-900/80 pb-3">
                        <h3 class="text-sm font-black uppercase text-indigo-300 tracking-wider">
                            <?php echo __('judge_panel_title'); ?>
                        </h3>
                        <span class="bg-indigo-900/80 border border-indigo-700 text-indigo-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                            ⚖️ <?php echo get_role_label('Judge'); ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Form 1: Cancel all votings -->
                        <form method="POST" class="bg-slate-900 p-4 rounded-lg border border-slate-800 space-y-3 flex flex-col justify-between">
                            <input type="hidden" name="action" value="judge_cancel_votings">
                            <p class="text-xs text-slate-300 leading-relaxed">
                                <?php echo __('desc_judge'); ?>
                            </p>
                            <button type="submit" class="w-full bg-rose-700 hover:bg-rose-800 text-white font-bold text-xs py-2.5 rounded-lg uppercase tracking-wider transition shadow">
                                <?php echo __('judge_cancel_all_btn'); ?>
                            </button>
                        </form>

                        <!-- Form 2: Kick only 1 selected player -->
                        <form method="POST" class="bg-slate-900 p-4 rounded-lg border border-slate-800 space-y-3 flex flex-col justify-between">
                            <input type="hidden" name="action" value="judge_kick_one_player">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-300">
                                    <?php echo __('judge_select_player_to_kick'); ?>
                                </label>
                                <select name="player_id" required class="w-full bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded-lg p-2.5 focus:outline-none focus:border-indigo-500">
                                    <option value=""><?php echo __('make_selection'); ?></option>
                                    <?php foreach ($db['players'] as $p): ?>
                                        <?php if ($p['status'] === 'alive'): ?>
                                            <option value="<?php echo htmlspecialchars($p['id']); ?>">
                                                <?php echo htmlspecialchars($p['name']); ?> (<?php echo get_role_label($p['role']); ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2.5 rounded-lg uppercase tracking-wider transition shadow">
                                <?php echo __('judge_kick_one_btn'); ?>
                            </button>
                        </form>
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
                    <?php endif; ?>

                    <!-- Role Share Form -->
                    <div id="role-action-container">
                        <?php if (!($db['roles_shared'] ?? false)): ?>
                            <!-- Share Roles Button Trigger -->
                            <button type="button" onclick="document.getElementById('roles-config-modal').classList.remove('hidden')" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-lg font-bold text-xs uppercase tracking-wider transition shadow flex items-center justify-center gap-2">
                                <span>📦</span> <?php echo __('distribute_roles_start'); ?>
                            </button>

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
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2.5 py-1 rounded border border-slate-700">
                                                        <?php echo __('toggle_alive_dead'); ?>
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
                let lastPhaseState = "<?php echo $db['phase']; ?>";
                let lastWinnerState = <?php echo json_encode($db['winner'] ?? null); ?>;
                let lastGraveRevealState = <?php echo json_encode($db['grave_keeper_revealed_roles'] ?? false); ?>;
                let lastGraveChargesState = <?php echo json_encode($db['grave_keeper_charges'] ?? 2); ?>;

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

                                    let roleTranslated = i18nRoles[p.role] || p.role;

                                    html += `
                                        <tr class="hover:bg-slate-800/50">
                                            <td class="p-3 font-semibold">${p.name}</td>
                                            <td class="p-3"><span class="px-2.5 py-1 rounded text-xs font-bold bg-slate-800 text-sky-300 border border-slate-700">${roleTranslated}</span></td>
                                            <td class="p-3"><span class="text-xs font-bold ${statusClass}">${statusText}</span></td>
                                            <td class="p-3 text-right rtl:text-left space-x-2 flex flex-wrap justify-end gap-1">
                                                ${kickButton}
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="player_id" value="${p.id}">
                                                    <button type="submit" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2.5 py-1 rounded border border-slate-700">
                                                        ${i18nTxt.toggle}
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
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
