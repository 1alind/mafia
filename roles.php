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
    <link rel="stylesheet" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/style.css?v=2">
    <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/script.js?v=2"></script>
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
