<?php
require_once 'actions.php';

if (!$is_host && !$needs_host_claim) {
    header("Location: player.php");
    exit;
}

$db = get_db();
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mafia Game - Host Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans">
    <div class="max-w-6xl mx-auto px-4 py-8">
        
        <?php if ($needs_host_claim): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center max-w-md mx-auto mt-20 shadow-2xl">
                <h1 class="text-2xl font-bold mb-4">دەستپێکرنا مێڤانێ یاریێ (Host)</h1>
                <p class="text-slate-400 mb-6">هیچ مێڤانەک بۆ ڤێ یاریێ نەهاتیە دیارکرن. تو دکاری خۆ بوەک مێڤان تۆمار بکەی.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="claim_host">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-xl transition duration-200 shadow-lg shadow-red-600/30">
                        وەرگرتنا مێڤانەریێ
                    </button>
                </form>
            </div>
        <?php else: ?>
            
            <div class="flex flex-col md:flex-row justify-between items-center bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8 shadow-xl gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-rose-400">ڕێڤەبەرێ یاریێ (Host Dashboard)</h1>
                    <p class="text-slate-400 text-sm mt-1">
                        رەوش: <span class="font-semibold text-amber-400 uppercase"><?= htmlspecialchars($db['phase']) ?></span> | 
                        رۆژ: <span class="font-semibold text-slate-200"><?= $db['day'] ?></span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <form method="POST" onsubmit="return confirm('تە پشتڕاستە تو دکازی وەرزگوهۆڕینێ بکەی؟');">
                        <input type="hidden" name="action" value="next_phase">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-5 rounded-xl transition duration-200 shadow-lg text-sm">
                            گوهۆڕینا قۆناغێ ➡️
                        </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('تە پشتڕاستە دکازی ڕێستێ بکەی و هەمی تشت بڕێژی؟');">
                        <input type="hidden" name="action" value="hard_reset">
                        <button type="submit" class="bg-red-900/50 hover:bg-red-900 text-red-200 border border-red-700 font-bold py-2.5 px-5 rounded-xl transition duration-200 text-sm">
                            پاشڤەبرنا یاریێ 🔄
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-8">
                    
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                        <h2 class="text-xl font-bold mb-4 text-slate-200">لیستا یاریزانان (<?= count($db['players']) ?>)</h2>
                        
                        <?php if (!$db['roles_shared']): ?>
                            <form method="POST" action="actions.php" class="bg-slate-950/50 p-4 rounded-xl border border-slate-800 mb-6 flex flex-col sm:flex-row items-center gap-4">
                                <input type="hidden" name="action" value="share_roles">
                                <div class="w-full sm:w-auto flex-1">
                                    <label class="block text-xs font-medium text-slate-400 mb-1">ژمارا مافیایان:</label>
                                    <input type="number" name="mafia_count" min="1" max="5" value="2" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm">
                                </div>
                                <button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-lg transition duration-200 shadow-lg text-sm mt-auto">
                                    دابەشکرنا رۆلان و دەستپێکرنا یاریێ 🎮
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="flex gap-3 mb-6">
                                <form method="POST" action="actions.php">
                                    <input type="hidden" name="action" value="hide_roles">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition">
                                        یاریەکا نوو (Rematch) 🔁
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-sm">
                                <thead class="bg-slate-950 text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th class="p-3">ناڤ</th>
                                        <th class="p-3">رۆل</th>
                                        <th class="p-3">رەوش</th>
                                        <th class="p-3">کریار</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php if (empty($db['players'])): ?>
                                        <tr><td colspan="4" class="p-4 text-center text-slate-500">هیچ یاریزانەک نەهاتیە ژوور.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($db['players'] as $p): ?>
                                            <tr class="hover:bg-slate-800/50">
                                                <td class="p-3 font-medium text-slate-200"><?= htmlspecialchars($p['name']) ?></td>
                                                <td class="p-3 text-amber-400"><?= htmlspecialchars($p['role']) ?></td>
                                                <td class="p-3">
                                                    <span class="px-2 py-1 rounded-full text-xs font-bold <?= $p['status'] === 'alive' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-red-950 text-red-400 border border-red-800' ?>">
                                                        <?= $p['status'] === 'alive' ? 'مایە' : 'مرۆ' ?>
                                                    </span>
                                                </td>
                                                <td class="p-3 flex gap-2">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="player_id" value="<?= $p['id'] ?>">
                                                        <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1 rounded text-xs">گوهۆڕینا رەوشی</button>
                                                    </form>
                                                    <?php if ($db['phase'] === 'day' && $p['status'] === 'alive'): ?>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="kick_player_day">
                                                            <input type="hidden" name="player_id" value="<?= $p['id'] ?>">
                                                            <button type="submit" class="bg-red-950 hover:bg-red-900 text-red-300 px-2.5 py-1 rounded text-xs border border-red-800">دەرئێخستن</button>
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

                    <?php if ($db['phase'] === 'night'): ?>
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                            <h2 class="text-xl font-bold mb-4 text-slate-200">کۆنتڕۆلا چالاکیێن شەڤێ 🌙</h2>
                            
                            <div class="space-y-4">
                                <?php 
                                $active_roles_list = ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Police', 'Town Doctor', 'Investigator'];
                                foreach ($active_roles_list as $role_item):
                                ?>
                                    <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex flex-col sm:flex-row justify-between items-center gap-3">
                                        <span class="font-semibold text-slate-300 text-sm"><?= $role_item ?>:</span>
                                        <div class="flex items-center gap-3 w-full sm:w-auto">
                                            <select onchange="recordNightTarget('<?= $role_item ?>', this.value)" class="bg-slate-900 border border-slate-700 text-white text-sm rounded-lg px-3 py-1.5 w-full sm:w-64">
                                                <option value="">-- دیارکرنا هدفەکێ --</option>
                                                <?php foreach ($db['players'] as $pl): ?>
                                                    <?php if ($pl['status'] === 'alive'): ?>
                                                        <option value="<?= $pl['id'] ?>" <?= (isset($db['night_actions'][$role_item]) && $db['night_actions'][$role_item] === $pl['name']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($pl['name']) ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-6 bg-slate-950 p-4 rounded-xl border border-slate-800">
                                <h3 class="font-bold text-slate-200 mb-2 text-sm">پرسیارا حارس المقبرة (Grave Keeper):</h3>
                                <p class="text-xs text-slate-400 mb-3">ما ماوەیێ مانێ هەی و حارس المقبرة دڤێت رۆلان ئاشکرا بکەت؟ (مایی: <?= $db['grave_keeper_charges'] ?? 2 ?>)</p>
                                <form method="POST" class="flex gap-3">
                                    <input type="hidden" name="action" value="answer_grave_keeper_reveal">
                                    <button type="submit" name="reveal_answer" value="yes" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1.5 px-4 rounded-lg text-xs">بەلێ (ئاشکرا بکە)</button>
                                    <button type="submit" name="reveal_answer" value="no" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-1.5 px-4 rounded-lg text-xs">نەخێر</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="space-y-8">
                    
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                        <h2 class="text-xl font-bold mb-4 text-slate-200">ڕاپۆرتا شەڤا دەربازبووی 📋</h2>
                        <?php if (!empty($db['investigation_results'])): ?>
                            <div class="mb-4 bg-slate-950 p-3 rounded-xl border border-slate-800">
                                <h4 class="text-xs font-bold text-amber-400 mb-1">ئەنجامێ ڤەکۆلینێ (Investigator):</h4>
                                <?php foreach ($db['investigation_results'] as $res): ?>
                                    <p class="text-xs text-slate-300">هدف: <?= htmlspecialchars($res['target']) ?> -> ئەنجام: <span class="text-white font-bold"><?= htmlspecialchars($res['result']) ?></span></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($db['grave_keeper_revealed_roles'] ?? false): ?>
                            <div class="mb-4 bg-slate-950 p-3 rounded-xl border border-slate-800">
                                <h4 class="text-xs font-bold text-indigo-400 mb-1">رۆلێن مرینان (حارس المقبرة):</h4>
                                <ul class="list-disc list-inside text-xs text-slate-300 space-y-1">
                                    <?php 
                                    $has_dead = false;
                                    foreach ($db['players'] as $p) {
                                        if ($p['status'] === 'dead') {
                                            echo "<li>" . htmlspecialchars($p['role']) . "</li>";
                                            $has_dead = true;
                                        }
                                    }
                                    if (!$has_dead) echo "<li>هیچ یاریزانەک مرۆ نینە.</li>";
                                    ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($db['last_night_report']): ?>
                            <div class="text-xs space-y-2 text-slate-300">
                                <p><strong class="text-red-400">یاریزانێن مرین:</strong> <?= empty($db['last_night_report']['killed_names']) ? 'چ کەس نەمرن' : implode(', ', $db['last_night_report']['killed_names']) ?></p>
                                <p><strong class="text-emerald-400">یاریزانێن هاتنە پاراستن:</strong> <?= empty($db['last_night_report']['saved_names']) ? 'چ کەس نەهاتنە پاراستن' : implode(', ', $db['last_night_report']['saved_names']) ?></p>
                            </div>
                        <?php else: ?>
                            <p class="text-xs text-slate-500">هێشتا ڕاپۆرتەک نینە.</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                        <h2 class="text-xl font-bold mb-4 text-slate-200">تۆمارێن چالاکیان (Logs)</h2>
                        <div class="bg-slate-950 border border-slate-800 rounded-xl p-4 h-64 overflow-y-auto space-y-2 text-xs font-mono text-slate-400">
                            <?php foreach (array_reverse($db['logs'] ?? []) as $log): ?>
                                <div class="border-b border-slate-900 pb-1"><?= htmlspecialchars($log) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

            </div>

        <?php endif; ?>

    </div>

    <script>
    function recordNightTarget(role, targetId) {
        const formData = new FormData();
        formData.append('action', 'record_night_target');
        formData.append('role', role);
        formData.append('target_id', targetId);

        fetch('actions.php', {
            method: 'POST',
            body: formData
        });
    }
    </script>
</body>
</html>
