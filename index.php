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
    'Suicidal Bomb',
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
    <title><?php echo __('host_panel_title'); ?> - Mafia Game</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.currentLang = "<?php echo get_current_lang(); ?>";
        window.isRolesShared = <?php echo ($db['roles_shared'] ?? false) ? 'true' : 'false'; ?>;
        window.serverPlayers = <?php echo json_encode($db['players'] ?? []); ?>;
        window.serverResetToken = <?php echo json_encode($db['reset_token'] ?? ''); ?>;
        window.i18nRoles = <?php echo json_encode($role_i18n_map); ?>;
        window.i18nTxt = {
            phaseSetup: "<?php echo __('phase_setup'); ?>",
            phaseNight: "<?php echo __('phase_night'); ?>",
            phaseDay: "<?php echo __('phase_day'); ?>",
            gameOver: "<?php echo __('game_over'); ?>",
            alive: "<?php echo __('alive'); ?>",
            dead: "<?php echo __('dead'); ?>",
            aliveTemp: "<?php echo __('alive_temporarily_mirhas'); ?>",
            voteKick: "<?php echo __('vote_kick_daytime'); ?>",
            toggle: "<?php echo __('toggle_alive_dead'); ?>",
            noPlayers: "<?php echo __('no_players_registered'); ?>",
            chargesLeft: "<?php echo __('charges_left'); ?>",
            revealedYes: "<?php echo __('revealed_status_yes'); ?>",
            revealedNo: "<?php echo __('revealed_status_no'); ?>",
            noneNoSelection: "<?php echo __('none_no_selection'); ?>",
            confirm: "<?php echo __('confirm'); ?>",
            cancel: "<?php echo __('cancel'); ?>",
            selected: "<?php echo __('selected'); ?>"
        };
    </script>
    <script src="scripts.js?v=<?php echo time(); ?>" defer></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 md:p-6 font-sans">
    <div id="main-container" class="max-w-5xl mx-auto space-y-6">
        
        <!-- TOP TOOLBAR: Language & Audio Control -->
        <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-900/90 border border-slate-800 p-3.5 rounded-xl shadow-lg">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 text-xs text-slate-400 font-bold uppercase tracking-wider">
                    🌐 <?php echo __('language'); ?>:
                    <?php render_language_selector(); ?>
                </div>
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
                <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-lg border border-slate-850">
                    <button type="button" onclick="setTimerPreset(30)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">30s</button>
                    <button type="button" onclick="setTimerPreset(60)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">1m</button>
                    <button type="button" onclick="setTimerPreset(120)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">2m</button>
                    <button type="button" onclick="setTimerPreset(180)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">3m</button>
                    <button type="button" onclick="setTimerPreset(300)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">5m</button>
                    <button type="button" onclick="setTimerPreset(600)" class="px-2.5 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-white rounded text-xs font-bold transition">10m</button>
                </div>
                
                <div class="flex items-center gap-3 bg-slate-950 px-4 py-2 rounded-xl border border-slate-800 min-w-[220px] justify-between">
                    <div id="timer-display" class="text-2xl font-black font-mono tracking-wider text-indigo-400">02:00</div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="timer-play-btn" onclick="toggleTimer()" class="p-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition text-xs font-bold" title="Start/Pause">
                            ▶️
                        </button>
                        <button type="button" onclick="resetTimer()" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-xs font-bold" title="Reset">
                            🔄
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- HEADER -->
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

            <?php if ($db['roles_shared'] ?? false): ?>
                <!-- CLIENT-SIDE GAMEPLAY UI CONTAINER -->
                <div id="client-gameplay-container" class="space-y-6">
                    <div class="bg-slate-900 p-8 rounded-xl text-center text-slate-400 border border-slate-800 font-bold text-sm animate-pulse">
                        ⌛ Loading Game Interface...
                    </div>
                </div>
            <?php else: ?>
                <!-- LOBBY & SETUP PHASE -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6 bg-slate-900 border border-slate-800 p-6 rounded-xl shadow-xl">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                            <div>
                                <h2 class="text-lg font-black uppercase tracking-wider text-rose-400"><?php echo __('connected_players'); ?></h2>
                                <p class="text-xs text-slate-400"><?php echo __('share_link_and_wait'); ?></p>
                            </div>
                            <div class="text-xs font-bold bg-slate-800 px-3 py-1.5 rounded border border-slate-700 text-sky-400">
                                <?php echo __('connected_players'); ?> <span id="online-count"><?php echo count($db['players']); ?></span>
                            </div>
                        </div>

                        <!-- Role Share Form -->
                        <div id="role-action-container">
                            <button type="button" onclick="document.getElementById('roles-config-modal').classList.remove('hidden')" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-lg font-bold text-xs uppercase tracking-wider transition shadow flex items-center justify-center gap-2">
                                <span>📦</span> <?php echo __('distribute_roles_start'); ?>
                            </button>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="action" value="add_bot">
                                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 border border-slate-700 text-sky-400 hover:text-sky-300 px-4 py-2.5 rounded-lg font-bold text-xs uppercase tracking-wider transition shadow flex items-center justify-center gap-2">
                                        <span>🤖</span> +1 Bot Player
                                    </button>
                                </form>
                                <form method="POST" class="w-full">
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

                                    <div class="flex items-center justify-between bg-slate-950 p-3 rounded-lg border border-slate-800">
                                        <label for="mafia_count" class="text-xs text-slate-300 font-bold"><?php echo __('exact_mafia_count'); ?></label>
                                        <input type="number" id="mafia_count" name="mafia_count" value="2" min="1" max="15" 
                                               class="w-16 bg-slate-900 border border-slate-700 text-center text-white text-sm font-bold rounded p-1.5 focus:outline-none focus:border-rose-500">
                                    </div>

                                    <div class="space-y-2">
                                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">کۆنترۆلا ڕۆلێن یاریێ (تەخسیسکرن):</span>
                                        <div class="grid grid-cols-2 gap-2 text-xs text-slate-300 bg-slate-950 p-4 rounded-lg border border-slate-800 max-h-48 overflow-y-auto">
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Mafia Doctor" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Mafia Doctor'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Deceiver" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Deceiver'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Police" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Police'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Town Doctor" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Town Doctor'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Investigator" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Investigator'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Judge" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Judge'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Grave Keeper" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Grave Keeper'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Mirhas" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Mirhas'); ?></label>
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-white transition"><input type="checkbox" name="special_roles[]" value="Suicidal Bomb" checked class="accent-emerald-500 rounded"> <?php echo get_role_label('Suicidal Bomb'); ?></label>
                                        </div>
                                    </div>

                                    <div class="text-[11px] text-slate-400 bg-emerald-950/20 border border-emerald-900/60 p-3 rounded-lg leading-relaxed space-y-1">
                                        <p class="font-bold text-emerald-400">💡 تێبینی / Note:</p>
                                        <p>ژمارا وەلاتی و مافیایێن ئاسایی بێ سنوورە. ئەگەر تە بۆ نموونە ٣ مافیا هەڵبژارتن، ۱ دێ بیتە سەرۆکێ مافیایێ، یەک دێ بیتە دکتورێ مافیایێ (ئەگەر کارا بیت) و یێ دی دێ بیتە مافیایێ ئاسایی. هەمان یاسا بۆ وەلاتیێن ئاسایی ژی یا ب جهە.</p>
                                    </div>

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
                        </div>

                        <!-- Lobby Players Table -->
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
                                                    <span class="text-xs font-bold text-emerald-400">
                                                        <?php echo __('alive'); ?>
                                                    </span>
                                                </td>
                                                <td class="p-3 text-right rtl:text-left space-x-2 flex flex-wrap justify-end gap-1">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="remove_player_setup">
                                                        <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                                                        <button type="submit" class="text-xs text-rose-400 hover:text-white bg-rose-950/60 px-2.5 py-1 rounded border border-rose-900/60 transition">
                                                            <?php echo get_current_lang() === 'ku' ? 'ژێبرن' : (get_current_lang() === 'ar' ? 'حذف' : 'Remove'); ?>
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

                    <!-- Setup Sidebar -->
                    <div class="space-y-6">
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-4 shadow-lg">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400"><?php echo __('phase_management'); ?></h2>
                            <div class="bg-slate-950 border border-slate-800 p-4 rounded-lg text-center space-y-2">
                                <span class="text-xs text-slate-500 uppercase block"><?php echo __('current_phase'); ?></span>
                                <div class="text-xl font-black uppercase text-rose-400" id="phase-label">
                                    <?php echo __('phase_setup'); ?>
                                </div>
                            </div>
                            <div class="bg-amber-950/40 border border-amber-900/60 p-3 rounded-lg text-center text-xs text-amber-300 font-bold uppercase">
                                <?php echo __('unlock_phase_notice'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>
