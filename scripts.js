// Mafia Game - Client-Side Scripts
// Helper script for Mafia Game UI
console.log("Mafia Game Client initialized");

// Placeholder for WebSocket implementation
try {
    const socket = new WebSocket('ws://' + window.location.host + '/ws');
    socket.onopen = () => console.log('WebSocket connected');
    socket.onmessage = (event) => console.log('WebSocket message:', event.data);
    socket.onclose = () => console.log('WebSocket disconnected');
} catch (e) {
    console.warn("WebSocket initialization failed: ", e);
}

document.addEventListener('submit', async function(e) {
    if (e.defaultPrevented) return;
    if (e.target.tagName === 'FORM') {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Add the submitter button's name/value if it exists
        if (e.submitter && e.submitter.name) {
            formData.append(e.submitter.name, e.submitter.value);
        }
        
        // Handle record_night_target locally
        if (formData.get('action') === 'record_night_target' || formData.get('action') === 'answer_grave_keeper_reveal') {
            if (!window.localNightActions) window.localNightActions = {};
            const role = formData.get('role') || 'Grave Keeper';
            const val = formData.get('target_id') || formData.get('reveal_answer') || '';
            if (val !== '') {
                window.localNightActions[role] = val;
            }
            console.log('Action stored locally:', window.localNightActions);
            
            const btn = e.target.querySelector('button[type="submit"]');
            if (btn) {
                const originalText = btn.innerText;
                btn.innerText = '✅ Saved';
                setTimeout(() => btn.innerText = originalText, 2000);
            }
            
            const targetSelect = e.target.querySelector('.target-select');
            let targetName = '';
            if (targetSelect && targetSelect.selectedIndex !== -1) {
                targetName = targetSelect.options[targetSelect.selectedIndex].text;
            }
            const targetDisplay = e.target.querySelector('.target-display');
            if (targetDisplay) {
                targetDisplay.innerText = (targetSelect && targetSelect.value !== '') ? `Target: ${targetName.trim()}` : '';
            }
            const buttonsContainer = e.target.querySelector('.buttons-container');
            if (buttonsContainer) buttonsContainer.classList.add('hidden');
            const cancelBtn = e.target.querySelector('.cancel-btn');
            if (cancelBtn) cancelBtn.classList.add('hidden');
            
            return;
        }

        // Special handling for Next Phase to submit local actions
        if (formData.get('action') === 'next_phase') {
            if (!window.localNightActions) window.localNightActions = {};
            document.querySelectorAll('[data-role-card]').forEach(card => {
                const role = card.getAttribute('data-role-card');
                const select = card.querySelector('.target-select');
                if (role && select && select.value !== '') {
                    window.localNightActions[role] = select.value;
                }
            });

            if (Object.keys(window.localNightActions).length > 0) {
                 const bulkFormData = new FormData();
                 bulkFormData.append('action', 'submit_all_night_actions');
                 bulkFormData.append('actions', JSON.stringify(window.localNightActions));
                 bulkFormData.append('ajax', '1');
                 
                 await fetch('actions.php?ajax=1', {
                     method: 'POST',
                     body: bulkFormData
                  });
                 window.localNightActions = {}; // Clear after successful submit
            }
        }
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            if (formData.get('action') === 'next_phase' && typeof pollHost === 'function') {
                 pollHost();
            }
            
            if (response.ok) {
                console.log('Form submitted successfully via AJAX');
                
                // If this is a rematch, reset, session reset, share roles, or claim host, perform a clean full page reload!
                const actionValue = formData.get('action');
                if (actionValue === 'hide_roles' || actionValue === 'hard_reset' || actionValue === 'reset_session' || actionValue === 'share_roles' || actionValue === 'claim_host') {
                    window.location.reload();
                    return;
                }

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newContainer = doc.getElementById('main-container');
                const currentContainer = document.getElementById('main-container');
                if (newContainer && currentContainer) {
                    currentContainer.innerHTML = newContainer.innerHTML;
                } else if (doc.body) {
                    document.body.innerHTML = doc.body.innerHTML;
                }

                // Close modals if open
                const rolesModal = document.getElementById('roles-config-modal');
                if (rolesModal && !rolesModal.classList.contains('hidden')) {
                    rolesModal.classList.add('hidden');
                }
            }
        } catch (err) {
            console.error('Submit error:', err);
        }
    }
});

// Modal handler for Roles Guide (0 page reloads)
document.addEventListener('click', function(e) {
    const link = e.target.closest('a[href="roles.php"]');
    if (link) {
        e.preventDefault();
        let modal = document.getElementById('roles-guide-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'roles-guide-modal';
            modal.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-lg w-full max-h-[85vh] flex flex-col shadow-2xl overflow-hidden">
                    <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-950">
                        <h3 class="font-bold text-sm text-slate-200">📖 Role Guide</h3>
                        <button type="button" onclick="document.getElementById('roles-guide-modal').remove()" class="text-slate-400 hover:text-white font-bold text-lg px-2">✕</button>
                    </div>
                    <div id="roles-guide-content" class="p-4 overflow-y-auto space-y-3 text-xs text-slate-300">
                        <div class="text-center py-6 animate-pulse text-slate-400">Loading guide...</div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            modal.classList.remove('hidden');
        }
        fetch('roles.php')
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const container = doc.querySelector('.container') || doc.body;
                const contentEl = document.getElementById('roles-guide-content');
                if (contentEl && container) {
                    contentEl.innerHTML = container.innerHTML;
                    const backBtns = contentEl.querySelectorAll('a[href], button');
                    backBtns.forEach(btn => {
                        if (btn.innerText.includes('Back') || btn.innerText.includes('گەڕیان') || btn.innerText.includes('عودة')) {
                            btn.remove();
                        }
                    });
                }
            });
    }
});

// Mafia Game - Host Server Traffic & State Engine
                // --- SERVER TRAFFIC & PING LOGGER ---
                (function() {
                    const originalFetch = window.fetch;
                    window.fetch = async function(...args) {
                        const startTime = Date.now();
                        const timeStr = new Date().toLocaleTimeString();
                        let url = typeof args[0] === 'string' ? args[0] : (args[0] && args[0].url ? args[0].url : 'server');
                        let method = 'GET';
                        let actionName = '';

                        if (args[1] && args[1].method) {
                            method = args[1].method.toUpperCase();
                        }

                        if (args[1] && args[1].body instanceof FormData) {
                            actionName = args[1].body.get('action') || '';
                        } else if (url.includes('actions.php?ajax=1')) {
                            actionName = 'poll_sync';
                        }

                        try {
                            const response = await originalFetch.apply(this, args);
                            const duration = Date.now() - startTime;
                            logServerHitUI(timeStr, method, url, actionName, response.status, response.statusText, duration, response.ok);
                            return response;
                        } catch (err) {
                            const duration = Date.now() - startTime;
                            logServerHitUI(timeStr, method, url, actionName, 'ERR', err.message || 'Error', duration, false);
                            throw err;
                        }
                    };
                })();

                function logServerHitUI(timeStr, method, url, actionName, status, statusText, duration, isOk) {
                    const container = document.getElementById('server-hit-logs');
                    if (!container) return;

                    const placeholder = container.querySelector('.placeholder-text');
                    if (placeholder) {
                        placeholder.remove();
                    }

                    const entry = document.createElement('div');
                    entry.className = 'border-b border-slate-900 pb-1.5 pt-1 flex items-center justify-between gap-2 text-[11px] leading-tight';

                    let icon = isOk ? '🟢' : '🔴';
                    let badgeClass = isOk ? 'bg-emerald-950/80 text-emerald-400 border-emerald-800' : 'bg-rose-950/80 text-rose-400 border-rose-800';
                    let shortUrl = url.split('/').pop().split('?')[0] || url;
                    if (url.includes('actions.php')) shortUrl = 'actions.php';
                    let actionBadge = actionName ? `<span class="text-amber-300 font-bold ml-1">(${actionName})</span>` : '';

                    entry.innerHTML = `
                        <div class="flex items-center gap-1.5 overflow-hidden text-ellipsis whitespace-nowrap">
                            <span>${icon}</span>
                            <span class="text-slate-500 font-mono">[${timeStr}]</span>
                            <span class="font-bold text-sky-400">${method}</span>
                            <span class="text-slate-300 font-medium">${shortUrl}</span>
                            ${actionBadge}
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-1.5 py-0.2 rounded border font-mono font-bold ${badgeClass}">${status}</span>
                            <span class="text-slate-500 text-[10px] font-mono">${duration}ms</span>
                        </div>
                    `;

                    container.prepend(entry);

                    // Keep up to 60 logs
                    while (container.children.length > 60) {
                        container.removeChild(container.lastChild);
                    }
                }

                function clearServerHitLogs() {
                    const container = document.getElementById('server-hit-logs');
                    if (container) {
                        container.innerHTML = '<div class="placeholder-text text-slate-500 italic text-center py-3">Monitoring host server requests...</div>';
                    }
                }

                const i18nRoles = window.i18nRoles;
                const i18nTxt = window.i18nTxt;

                let lastRolesSharedState = window.lastRolesSharedState;
                
                // --- DYNAMIC CLIENT-SIDE STATE MANAGER & LAYOUT ENGINE (LOCAL-FIRST ARCHITECTURE) ---
                window.translations = window.translations || {};
                window.serverResetToken = window.serverResetToken || '';
                window.serverPlayers = window.serverPlayers || [];
                window.isRolesShared = window.isRolesShared || false;
                window.allGameRoles = window.allGameRoles || [];

                function __(key, ...args) {
                    if (!window.translations) return key;
                    let text = window.translations[key] || key;
                    if (args.length > 0) {
                        for (let i = 0; i < args.length; i++) {
                            text = text.replace('%s', args[i]).replace('%d', args[i]);
                        }
                    }
                    return text;
                }

                function getRoleLabel(role) {
                    if (!role || role === 'Pending') {
                        return __('role_pending');
                    }
                    const map = {
                        'Mafia Boss': 'role_mafia_boss',
                        'Mafia Doctor': 'role_mafia_doctor',
                        'Deceiver': 'role_deceiver',
                        'Regular Mafia': 'role_regular_mafia',
                        'Police': 'role_police',
                        'Town Doctor': 'role_town_doctor',
                        'Investigator': 'role_investigator',
                        'Judge': 'role_judge',
                        'Grave Keeper': 'role_grave_keeper',
                        'Mirhas': 'role_mirhas',
                        'Suicidal Bomb': 'role_suicidal_bomb',
                        'Citizen': 'role_citizen',
                        'Pending': 'role_pending',
                        'Mafia': 'team_mafia'
                    };
                    if (map[role]) {
                        return __(map[role]);
                    }
                    return role;
                }

                function getGameState() {
                    try {
                        const data = localStorage.getItem('mafia_game_state');
                        return data ? JSON.parse(data) : null;
                    } catch (e) {
                        console.error("Error parsing game state:", e);
                        return null;
                    }
                }

                function saveGameState(state) {
                    try {
                        localStorage.setItem('mafia_game_state', JSON.stringify(state));
                    } catch (e) {
                        console.error("Error saving game state:", e);
                    }
                }

                function initializeGameState(serverPlayers, resetToken) {
                    try {
                        localStorage.removeItem('grave_keeper_revealed_roles');
                        localStorage.removeItem('mafia_gk_revealed');
                    } catch(e) {}
                    const state = {
                        phase: 'night',
                        day: 1,
                        players: serverPlayers.map(p => ({
                            id: p.id,
                            name: p.name,
                            role: p.role,
                            status: p.status || 'alive'
                        })),
                        night_actions: {},
                        logs: [
                            `🎮 ${__('logs_game_started_night_1') || 'Game started. Night 1 begun. Call roles and record night actions.'}`
                        ],
                        grave_keeper_charges: 2,
                        grave_keeper_revealed_roles: false,
                        grave_keeper_reveal_pending: false,
                        grave_keeper_acted_tonight: false,
                        revealed_hidden_roles: [],
                        gravedigger_charges: 2,
                        gravedigger_reveal_pending: false,
                        town_doctor_self_protect_count: 0,
                        last_night_report: null,
                        investigation_results: [],
                        delayed_departure: [],
                        suicidal_bomb_triggered_by: null,
                        winner: null,
                        reset_token: resetToken
                    };
                    saveGameState(state);
                    return state;
                }

                function localNextPhase() {
                    let state = getGameState();
                    if (!state) return;

                    if (state.phase === 'night') {
                        const nightActions = state.night_actions || {};
                        
                        const mafia_target_id = nightActions['Mafia'] || nightActions['Mafia Boss'] || null;
                        let mafia_target = null;
                        if (mafia_target_id) {
                            const p = state.players.find(pl => pl.id === mafia_target_id);
                            if (p) mafia_target = p.name;
                        }

                        const mafia_doc_target_id = nightActions['Mafia Doctor'] || null;
                        let mafia_doc_target = null;
                        if (mafia_doc_target_id) {
                            const p = state.players.find(pl => pl.id === mafia_doc_target_id);
                            if (p) mafia_doc_target = p.name;
                        }

                        const town_doc_target_id = nightActions['Town Doctor'] || null;
                        let town_doc_target = null;
                        if (town_doc_target_id) {
                            const p = state.players.find(pl => pl.id === town_doc_target_id);
                            if (p) town_doc_target = p.name;
                        }

                        const police_target_id = nightActions['Police'] || null;
                        let police_target = null;
                        if (police_target_id) {
                            const p = state.players.find(pl => pl.id === police_target_id);
                            if (p) police_target = p.name;
                        }

                        const suicidal_bomb_target_id = nightActions['Suicidal Bomb'] || null;
                        let suicidal_bomb_target = null;
                        if (suicidal_bomb_target_id) {
                            const p = state.players.find(pl => pl.id === suicidal_bomb_target_id);
                            if (p) suicidal_bomb_target = p.name;
                        }

                        if (town_doc_target) {
                            const docPlayer = state.players.find(p => p.name === town_doc_target && p.role === 'Town Doctor');
                            if (docPlayer) {
                                state.town_doctor_self_protect_count = (state.town_doctor_self_protect_count || 0) + 1;
                            }
                        }

                        let killed_names = [];
                        let saved_names = [];

                        let bomb_player_name = null;
                        if (mafia_target) {
                            if (mafia_target === town_doc_target || mafia_target === mafia_doc_target) {
                                saved_names.push(mafia_target);
                            } else {
                                killed_names.push(mafia_target);

                                const mafiaTargetPlayer = state.players.find(p => p.name === mafia_target);
                                if (mafiaTargetPlayer && mafiaTargetPlayer.role === 'Suicidal Bomb') {
                                    let mafia_shooter = null;
                                    const boss = state.players.find(p => p.role === 'Mafia Boss' && p.status === 'alive');
                                    if (boss) {
                                        mafia_shooter = boss.name;
                                    } else {
                                        const anyMaf = state.players.find(p => ['Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(p.role) && p.status === 'alive');
                                        if (anyMaf) mafia_shooter = anyMaf.name;
                                    }

                                    if (mafia_shooter && !killed_names.includes(mafia_shooter)) {
                                        killed_names.push(mafia_shooter);
                                        state.logs.push(`💣 Suicidal Bomb (${mafia_target}) was shot by Mafia! The bomb exploded, eliminating both the Suicidal Bomb and the Mafia shooter (${mafia_shooter})!`);
                                    }
                                }
                            }
                        }

                        if (police_target) {
                            const policePlayer = state.players.find(p => p.role === 'Police' && p.status === 'alive');
                            const police_player_name = policePlayer ? policePlayer.name : null;

                            const policeTargetPlayer = state.players.find(p => p.name === police_target);
                            const police_target_role = policeTargetPlayer ? policeTargetPlayer.role : '';

                            const is_mafia_target = ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(police_target_role);

                            if (!is_mafia_target) {
                                if (police_player_name && !killed_names.includes(police_player_name)) {
                                    killed_names.push(police_player_name);
                                    state.logs.push(`⚠️ Police (${police_player_name}) targeted an innocent Citizen (${police_target}) and was kicked out of the game! The Citizen survives.`);
                                }
                            } else {
                                if (police_target === town_doc_target || police_target === mafia_doc_target) {
                                    if (!saved_names.includes(police_target)) {
                                        saved_names.push(police_target);
                                    }
                                } else {
                                    if (!killed_names.includes(police_target)) {
                                        killed_names.push(police_target);
                                    }
                                }
                            }
                        }

                        if (suicidal_bomb_target) {
                            const bombPlayer = state.players.find(p => p.role === 'Suicidal Bomb' && p.status === 'alive');
                            bomb_player_name = bombPlayer ? bombPlayer.name : null;

                            if (bomb_player_name) {
                                if (!killed_names.includes(bomb_player_name)) {
                                    killed_names.push(bomb_player_name);
                                }
                                if (!killed_names.includes(suicidal_bomb_target)) {
                                    killed_names.push(suicidal_bomb_target);
                                }
                                saved_names = saved_names.filter(n => n !== bomb_player_name && n !== suicidal_bomb_target);
                                state.logs.push(`💥 Suicidal Bomb (${bomb_player_name}) detonated at night on ${suicidal_bomb_target}! Both were eliminated (bypassing doctor protection and roles).`);
                            }
                        }

                        let final_killed = [];
                        killed_names.forEach(kname => {
                            if (saved_names.includes(kname) && !(suicidal_bomb_target && (kname === bomb_player_name || kname === suicidal_bomb_target))) {
                                return;
                            }

                            const p = state.players.find(pl => pl.name === kname);
                            if (p) {
                                if (p.role === 'Mirhas') {
                                    const isBombDeath = (bomb_player_name && kname === bomb_player_name) || (suicidal_bomb_target && kname === suicidal_bomb_target);
                                    if (isBombDeath) {
                                        p.status = 'dead';
                                        final_killed.push(kname);
                                    } else if (!state.delayed_departure.includes(kname)) {
                                        state.delayed_departure.push(kname);
                                        state.logs.push(`Mirhas (${kname}) was targeted but stays alive for 1 day.`);
                                    } else {
                                        p.status = 'dead';
                                        final_killed.push(kname);
                                    }
                                } else {
                                    p.status = 'dead';
                                    final_killed.push(kname);
                                }
                            }
                        });

                        if (state.delayed_departure && state.delayed_departure.length > 0) {
                            state.delayed_departure.forEach(dname => {
                                const p = state.players.find(pl => pl.name === dname);
                                if (p) {
                                    p.status = 'dead';
                                    if (!final_killed.includes(dname)) {
                                        final_killed.push(dname);
                                    }
                                }
                            });
                            state.delayed_departure = [];
                        }

                        let diary = [];
                        const lang = translations.lang_code || 'ku';

                        if (mafia_target) {
                            diary.push({
                                en: `• 🔪 <strong>Mafia</strong> targeted <strong class='text-rose-400'>${mafia_target}</strong>.`,
                                ku: `• 🔪 <strong>مافیا</strong> تەقە ل <strong class='text-rose-400'>${mafia_target}</strong> کر.`,
                                ar: `• 🔪 <strong>المافيا</strong> استهدفت <strong class='text-rose-400'>${mafia_target}</strong>.`
                            });
                        } else {
                            diary.push({
                                en: `• 🔪 <strong>Mafia</strong> did not choose any target.`,
                                ku: `• 🔪 <strong>مافیا</strong> چ کەس کەنەکرە ئارمانج.`,
                                ar: `• 🔪 <strong>المافيا</strong> لم تختر أي هدف.`
                            });
                        }

                        if (town_doc_target) {
                            diary.push({
                                en: `• 🩺 <strong>Town Doctor</strong> protected <strong class='text-emerald-400'>${town_doc_target}</strong>.`,
                                ku: `• 🩺 <strong>نوژدارێ هاولاتی</strong> پاراستن ل <strong class='text-emerald-400'>${town_doc_target}</strong> کر.`,
                                ar: `• 🩺 <strong>طبيب البلدة</strong> قام بحماية <strong class='text-emerald-400'>${town_doc_target}</strong>.`
                            });
                        }

                        if (mafia_doc_target) {
                            diary.push({
                                en: `• 🧪 <strong>Mafia Doctor</strong> protected <strong class='text-rose-400'>${mafia_doc_target}</strong>.`,
                                ku: `• 🧪 <strong>نوژدارێ مافیا</strong> پاراستن ل <strong class='text-rose-400'>${mafia_doc_target}</strong> کر.`,
                                ar: `• 🧪 <strong>طبيب المافيا</strong> قام بحماية <strong class='text-rose-400'>${mafia_doc_target}</strong>.`
                            });
                        }

                        if (police_target) {
                            diary.push({
                                en: `• 👮 <strong>Police</strong> targeted <strong class='text-sky-400'>${police_target}</strong>.`,
                                ku: `• 👮 <strong>پۆلیس</strong> تەقە ل <strong class='text-sky-400'>${police_target}</strong> کر.`,
                                ar: `• 👮 <strong>الشرطي</strong> استهدف <strong class='text-sky-400'>${police_target}</strong>.`
                            });
                        }

                        const deceiver_target_id = nightActions['Deceiver'] || null;
                        let deceiver_target = null;
                        if (deceiver_target_id) {
                            const p = state.players.find(pl => pl.id === deceiver_target_id);
                            if (p) deceiver_target = p.name;
                        }
                        if (deceiver_target) {
                            diary.push({
                                en: `• 🎭 <strong>Deceiver</strong> disguised <strong class='text-violet-400'>${deceiver_target}</strong>.`,
                                ku: `• 🎭 <strong>فێلبازێ مافیا</strong> فێڵ ل سەر <strong class='text-violet-400'>${deceiver_target}</strong> کر.`,
                                ar: `• 🎭 <strong>مخادع المافيا</strong> قام بتمويه <strong class='text-violet-400'>${deceiver_target}</strong>.`
                            });
                        }

                        const investigator_target_id = nightActions['Investigator'] || null;
                        let investigator_target = null;
                        if (investigator_target_id) {
                            const p = state.players.find(pl => pl.id === investigator_target_id);
                            if (p) investigator_target = p.name;
                        }
                        if (investigator_target) {
                            const eval_res = evaluateInvestigationLocally(investigator_target, state);
                            diary.push({
                                en: `• 🔍 <strong>Investigator</strong> checked <strong class='text-amber-400'>${investigator_target}</strong> and found them as: <strong class='text-white underline'>${eval_res}</strong>.`,
                                ku: `• 🔍 <strong>ڤەکولەر</strong> ل سەر <strong class='text-amber-400'>${investigator_target}</strong> لێکۆڵینەوە کر و دیت کو ئەو یێ دیارە وەک: <strong class='text-white underline'>${eval_res}</strong>.`,
                                ar: `• 🔍 <strong>المحقق</strong> كشف على <strong class='text-amber-400'>${investigator_target}</strong> وظهر له كـ: <strong class='text-white underline'>${eval_res}</strong>.`
                            });
                        }

                        if (suicidal_bomb_target) {
                            diary.push({
                                en: `• 💣 <strong>Suicidal Bomb</strong> targeted <strong class='text-red-500'>${suicidal_bomb_target}</strong> for night explosion.`,
                                ku: `• 💣 <strong>بۆمبێ</strong> خۆ ل سەر <strong class='text-red-500'>${suicidal_bomb_target}</strong> بەرهەڤکر بۆ تەقاندنێ.`,
                                ar: `• 💣 <strong>الانتحاري</strong> استهدف <strong class='text-red-500'>${suicidal_bomb_target}</strong> للتفجير الليلة.`
                            });
                        }

                        if (mafia_target) {
                            let saved_by_doc = (mafia_target === town_doc_target || mafia_target === mafia_doc_target);
                            if (suicidal_bomb_target && mafia_target === bomb_player_name) {
                                saved_by_doc = false;
                            }

                            if (saved_by_doc) {
                                diary.push({
                                    en: `🛡️ <strong>Doctor Protection:</strong> <strong class='text-white'>${mafia_target}</strong> was shot by the Mafia but was <span class='text-emerald-400 font-bold underline'>successfully saved</span> by a Doctor!`,
                                    ku: `🛡️ <strong>پاراستنا نوژداری:</strong> تەقە ل <strong class='text-white'>${mafia_target}</strong> هاتە کرن ژ لایێ مافیایێ ڤە، بەس ژ لایێ نوژداری ڤە <span class='text-emerald-400 font-bold underline'>هاتە پاراستن</span>!`,
                                    ar: `🛡️ <strong>حماية الطبيب:</strong> تم إطلاق النار على <strong class='text-white'>${mafia_target}</strong> من المافيا ولكن تم <span class='text-emerald-400 font-bold underline'>إنقاذه بنجاح</span> بواسطة الطبيب!`
                                });
                            } else {
                                const targetPlayer = state.players.find(p => p.name === mafia_target);
                                const is_mirhas = targetPlayer && targetPlayer.role === 'Mirhas';
                                if (is_mirhas) {
                                    diary.push({
                                        en: `🛡️ <strong>Mirhas Resistance:</strong> <strong class='text-white'>${mafia_target}</strong> was shot but survives for 1 extra day due to Mirhas passive ability.`,
                                        ku: `🛡️ <strong>خۆڕاگرییا مێرخاسی:</strong> تەقە ل <strong class='text-white'>${mafia_target}</strong> هاتە کرن بەس ژ بەر شیانێن وی ئەو دێ بۆ ماوێ ۱ رۆژێ د زیندێ دا مینیت.`,
                                        ar: `🛡️ <strong>مقاومة ميرخاس:</strong> تم إطلاق النار على <strong class='text-white'>${mafia_target}</strong> ولكنه يبقى حياً ليوم واحد إضافي بسبب قدرته.`
                                    });
                                } else {
                                    diary.push({
                                        en: `💀 <strong>Eliminated:</strong> <strong class='text-white'>${mafia_target}</strong> was shot and died.`,
                                        ku: `💀 <strong>دەرکەفتن:</strong> تەقە ل <strong class='text-white'>${mafia_target}</strong> هاتە کرن و مریت.`,
                                        ar: `💀 <strong>تصفية:</strong> تم إطلاق النار على <strong class='text-white'>${mafia_target}</strong> وتوفي.`
                                    });
                                }
                            }
                        }

                        if (police_target) {
                            const policeTargetPlayer = state.players.find(p => p.name === police_target);
                            const is_mafia = policeTargetPlayer && ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(policeTargetPlayer.role);

                            if (!is_mafia) {
                                const policePlayer = state.players.find(p => p.role === 'Police');
                                const police_player_name = policePlayer ? policePlayer.name : 'Police';

                                diary.push({
                                    en: `⚠️ <strong>Police Penalty:</strong> Police targeted innocent <strong class='text-white'>${police_target}</strong>. Police player <strong class='text-white'>${police_player_name}</strong> died as penalty.`,
                                    ku: `⚠️ <strong>سزایێ پۆلیسی:</strong> پۆلیسی تەقە ل وەلاتیێ بێ گونەهـ <strong class='text-white'>${police_target}</strong> کر! پۆلیس خۆ ب خۆ <strong class='text-white'>${police_player_name}</strong> مریت.`,
                                    ar: `⚠️ <strong>عقوبة الشرطي:</strong> استهدف الشرطي مواطناً بريئاً <strong class='text-white'>${police_target}</strong>. توفي الشرطي <strong class='text-white'>${police_player_name}</strong> كعقوبة.`
                                });
                            } else {
                                const saved_by_doc = (police_target === town_doc_target || police_target === mafia_doc_target);
                                if (saved_by_doc) {
                                    diary.push({
                                        en: `🛡️ <strong>Doctor Protection:</strong> Mafia member <strong class='text-white'>${police_target}</strong> was shot by Police but <span class='text-emerald-400 font-bold underline'>saved by a Doctor</span>!`,
                                        ku: `🛡️ <strong>پاراستنا نوژداری:</strong> ئەندامێ مافیایێ <strong class='text-white'>${police_target}</strong> ژ لایێ پۆلیسی ڤە هاتە تەقکرن بەس نوژداری <span class='text-emerald-400 font-bold underline'>ئەو پاراست</span>!`,
                                        ar: `🛡️ <strong>حماية الطبيب:</strong> تم استهداف المافيا <strong class='text-white'>${police_target}</strong> من الشرطي ولكن تم <span class='text-emerald-400 font-bold underline'>إنقاذه بواسطة الطبيب</span>!`
                                    });
                                } else {
                                    diary.push({
                                        en: `💀 <strong>Mafia Dead:</strong> Mafia member <strong class='text-white'>${police_target}</strong> was shot by Police and died.`,
                                        ku: `💀 <strong>کوشتنا مافیایێ:</strong> ئەندامێ مافیایێ <strong class='text-white'>${police_target}</strong> ژ لایێ پۆلیسی ڤە هاتە کوشتن و مریت.`,
                                        ar: `💀 <strong>وفاة مافيا:</strong> تم إطلاق النار على المافيا <strong class='text-white'>${police_target}</strong> وتوفي.`
                                    });
                                }
                            }
                        }

                        if (suicidal_bomb_target && bomb_player_name) {
                            diary.push({
                                en: `💥 <strong>Bomb Detonation:</strong> Suicidal Bomb <strong class='text-white'>${bomb_player_name}</strong> exploded on <strong class='text-white'>${suicidal_bomb_target}</strong>. Both are dead.`,
                                ku: `💥 <strong>تەقینا بۆمبێ:</strong> بۆمبێ خۆ کەرتی <strong class='text-white'>${bomb_player_name}</strong> خۆ دگەل <strong class='text-white'>${suicidal_bomb_target}</strong> تەقاند. هەردوو مرن.`,
                                ar: `💥 <strong>تفجير الانتحاري:</strong> قام الانتحاري <strong class='text-white'>${bomb_player_name}</strong> بتفجير نفسه مع <strong class='text-white'>${suicidal_bomb_target}</strong>. كلاهما ماتا.`
                            });
                        }

                        let revealed_roles = {};
                        if (state.grave_keeper_reveal_pending || state.grave_keeper_revealed_roles) {
                            if (!Array.isArray(state.revealed_hidden_roles)) {
                                state.revealed_hidden_roles = [];
                            }
                            state.players.forEach(p => {
                                if (p.status === 'dead' || final_killed.includes(p.name)) {
                                    revealed_roles[p.name] = p.role || 'Citizen';
                                    if (p.role && !state.revealed_hidden_roles.includes(p.role)) {
                                        state.revealed_hidden_roles.push(p.role);
                                    }
                                }
                            });
                            const mafiaAlive = state.players.some(p => ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(p.role) && p.status === 'alive' && !final_killed.includes(p.name));
                            if (!mafiaAlive && !state.revealed_hidden_roles.includes('Mafia')) {
                                state.revealed_hidden_roles.push('Mafia');
                            }
                            state.grave_keeper_revealed_roles = true;
                            state.grave_keeper_reveal_pending = false;
                            state.gravedigger_reveal_pending = false;
                        }

                        state.last_night_report = {
                            killed_names: final_killed,
                            saved_names: Array.from(new Set(saved_names)),
                            diary_entries: diary,
                            revealed_roles: revealed_roles
                        };

                        state.phase = 'day';
                        state.night_actions = {};
                        state.grave_keeper_acted_tonight = false;
                        state.logs.push(`Night ${state.day} ended. Day ${state.day} started.`);

                        checkWinConditions(state);
                        playSound('daybreak');

                    } else if (state.phase === 'day') {
                        state.phase = 'night';
                        state.day = (state.day || 1) + 1;
                        state.last_night_report = null;
                        state.investigation_results = [];
                        state.grave_keeper_acted_tonight = false;
                        state.grave_keeper_revealed_roles = false;
                        state.grave_keeper_reveal_pending = false;
                        try {
                            localStorage.removeItem('grave_keeper_revealed_roles');
                            localStorage.removeItem('mafia_gk_revealed');
                        } catch(e) {}
                        state.logs.push(`Day ${state.day - 1} ended. Night ${state.day} started.`);

                        checkWinConditions(state);
                        playSound('nightfall');
                    }

                    saveGameState(state);
                    renderHostUI();
                }

                function evaluateInvestigationLocally(targetName, state) {
                    let targetRole = null;
                    let targetId = null;
                    state.players.forEach(p => {
                        if (p.name.trim() === targetName.trim()) {
                            targetRole = p.role || '';
                            targetId = p.id;
                        }
                    });

                    if (!targetRole) return 'Citizen';

                    if (targetRole === 'Mafia Boss') {
                        return 'Citizen';
                    }

                    const nightActions = state.night_actions || {};
                    const deceiver_target = nightActions['Deceiver'] || null;
                    const deceiverAlive = state.players.some(p => p.role === 'Deceiver' && p.status === 'alive');
                    
                    let isDeceived = false;
                    if (deceiverAlive && deceiver_target) {
                        const pDeceiver = state.players.find(pl => String(pl.id) === String(deceiver_target) || pl.name === deceiver_target);
                        if (pDeceiver && String(pDeceiver.id) === String(targetId)) {
                            isDeceived = true;
                        }
                    }

                    const isMafia = ['Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(targetRole);
                    if (isDeceived) {
                        return isMafia ? 'Citizen' : 'Regular Mafia';
                    } else {
                        return isMafia ? 'Regular Mafia' : 'Citizen';
                    }
                }

                function checkWinConditions(state) {
                    if (state.winner) return;

                    let aliveMafia = 0;
                    let aliveCitizens = 0;

                    state.players.forEach(p => {
                        if (p.status === 'alive' || state.delayed_departure.includes(p.name)) {
                            if (['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(p.role)) {
                                aliveMafia++;
                            } else {
                                aliveCitizens++;
                            }
                        }
                    });

                    if (aliveMafia === 0 && state.players.length > 0) {
                        state.winner = 'Citizens';
                        state.logs.push('🏆 GAME OVER: Citizens have won!');
                    } else if (aliveMafia >= aliveCitizens && aliveMafia > 0) {
                        state.winner = 'Mafia';
                        state.logs.push('🏆 GAME OVER: Mafia have won!');
                    }
                }

                function confirmNightAction(role) {
                    const select = document.getElementById(`select-${role}`);
                    if (!select) return;

                    const targetId = select.value;
                    const state = getGameState();
                    if (!state) return;

                    if (targetId) {
                        const p = state.players.find(pl => pl.id === targetId);
                        state.night_actions[role] = targetId;
                        state.logs.push(`🌙 Special role '${role}' recorded target: '${p ? p.name : targetId}'`);
                    } else {
                        delete state.night_actions[role];
                    }

                    saveGameState(state);
                    renderHostUI();
                }

                function cancelNightAction(role) {
                    const state = getGameState();
                    if (!state) return;

                    delete state.night_actions[role];
                    state.logs.push(`🌙 Cancelled night action for: '${role}'`);
                    saveGameState(state);
                    renderHostUI();
                }

                function confirmGkAction() {
                    const select = document.getElementById('gk-select');
                    if (!select) return;

                    const val = select.value;
                    if (!val) return;

                    const state = getGameState();
                    if (!state) return;

                    if (val === 'yes') {
                        state.grave_keeper_reveal_pending = true;
                        if ((state.grave_keeper_charges || 2) > 0) {
                            state.grave_keeper_charges = (state.grave_keeper_charges || 2) - 1;
                            state.gravedigger_charges = state.grave_keeper_charges;
                        }
                        state.grave_keeper_revealed_roles = true;
                        state.logs.push(`🪦 Grave Keeper decided to REVEAL dead players' roles tonight.`);
                    } else {
                        state.grave_keeper_reveal_pending = false;
                        state.logs.push(`🪦 Grave Keeper decided to KEEP dead players' roles hidden tonight.`);
                    }

                    state.grave_keeper_acted_tonight = true;

                    saveGameState(state);
                    renderHostUI();
                }

                function localLynchPlayer(playerId) {
                    const state = getGameState();
                    if (!state) return;

                    const p = state.players.find(pl => pl.id === playerId);
                    if (p) {
                        p.status = 'dead';
                        state.logs.push(`Player '${p.name}' was voted out/kicked during Day ${state.day}.`);
                        if (p.role === 'Suicidal Bomb') {
                            state.suicidal_bomb_triggered_by = p.name;
                            state.logs.push(`💣 Suicidal Bomb ('${p.name}') was voted out! Suicidal Bomb can now choose a player to be kicked out in revenge!`);
                        }

                        checkWinConditions(state);
                        saveGameState(state);
                        renderHostUI();
                    }
                }

                function localToggleStatus(playerId) {
                    const state = getGameState();
                    if (!state) return;

                    const p = state.players.find(pl => pl.id === playerId);
                    if (p) {
                        p.status = p.status === 'alive' ? 'dead' : 'alive';
                        state.logs.push(`Admin toggled status of player '${p.name}' to ${p.status}.`);

                        checkWinConditions(state);
                        saveGameState(state);
                        renderHostUI();
                    }
                }

                function confirmBombExplosion() {
                    const select = document.getElementById('bomb-revenge-select');
                    if (!select) return;

                    const targetId = select.value;
                    if (!targetId) return;

                    const state = getGameState();
                    if (!state) return;

                    const triggeredBy = state.suicidal_bomb_triggered_by || 'Suicidal Bomb';

                    if (targetId === 'none') {
                        state.logs.push(`🕊️ Suicidal Bomb (${triggeredBy}) decided to leave alone peacefully without taking anyone down.`);
                    } else {
                        const p = state.players.find(pl => pl.id === targetId && pl.status === 'alive');
                        if (p) {
                            p.status = 'dead';
                            state.logs.push(`💥 Suicidal Bomb (${triggeredBy}) triggered revenge explosion and eliminated '${p.name}'!`);
                        }
                    }

                    state.suicidal_bomb_triggered_by = null;
                    checkWinConditions(state);
                    saveGameState(state);
                    renderHostUI();
                }

                function leaveBombAlone() {
                    const state = getGameState();
                    if (!state) return;

                    const triggeredBy = state.suicidal_bomb_triggered_by || 'Suicidal Bomb';
                    state.logs.push(`🕊️ Suicidal Bomb (${triggeredBy}) decided to leave alone peacefully without taking anyone down.`);
                    state.suicidal_bomb_triggered_by = null;

                    checkWinConditions(state);
                    saveGameState(state);
                    renderHostUI();
                }

                function renderHostUI() {
                    const clientContainer = document.getElementById('client-gameplay-container');
                    if (!clientContainer) return;

                    const state = getGameState();
                    if (!state) return;

                    let html = '';

                    // 1. VICTORY BANNER
                    if (state.winner) {
                        const title = state.winner === 'Citizens' ? __('citizens_win_title') : __('mafia_win_title');
                        const desc = state.winner === 'Citizens' ? __('citizens_win_desc') : __('mafia_win_desc');
                        html += `
                            <div class="bg-indigo-950 border-4 border-indigo-500/80 p-8 rounded-2xl text-center space-y-6 shadow-2xl">
                                <span class="text-7xl block">🏆</span>
                                <h1 class="text-4xl font-black text-white uppercase tracking-wider">${title}</h1>
                                <p class="text-base text-indigo-200 max-w-lg mx-auto leading-relaxed">${desc}</p>
                                <div class="pt-4 flex justify-center">
                                    <form method="POST" id="rematch-form" class="inline">
                                        <input type="hidden" name="action" value="hide_roles">
                                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-xl font-bold text-sm uppercase tracking-wider shadow-lg transition">
                                            ${__('start_new_rematch') || '🔄 Start New Rematch'}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        `;
                        clientContainer.innerHTML = html;
                        return;
                    }

                    // Update phase label in timer card
                    const phaseLabel = document.getElementById('phase-label');
                    if (phaseLabel) {
                        let pName = state.phase === 'setup' ? __('phase_setup') : (state.phase === 'night' ? __('phase_night') : __('phase_day'));
                        phaseLabel.innerText = pName + ' ' + state.day;
                    }

                    // 2. NIGHT PHASE GUIDED ASSISTANT
                    if (state.phase === 'night') {
                        const gkCharges = state.grave_keeper_charges !== undefined ? state.grave_keeper_charges : 2;
                        const localStorageGkReveal = (localStorage.getItem('grave_keeper_revealed_roles') === 'true') || (localStorage.getItem('mafia_gk_revealed') === 'true');
                        const domGkReveal = (document.getElementById('hidden_gk_revealed')?.value === 'true') || (document.getElementById('gk_revealed_data_store')?.dataset?.revealed === 'true');
                        const gkRevealed = !!(state.grave_keeper_revealed_roles || localStorageGkReveal || domGkReveal);

                        if (gkRevealed) {
                            try {
                                localStorage.setItem('grave_keeper_revealed_roles', 'true');
                                localStorage.setItem('mafia_gk_revealed', 'true');
                            } catch (e) {}
                        }

                        function getRoleStatus(role, state, gkRevealed) {
                            if (role === 'Grave Keeper') {
                                const assignedGk = state.players.some(p => p.role === 'Grave Keeper');
                                const isGkDead = state.players.some(p => p.role === 'Grave Keeper' && (p.status === 'dead' || (state.delayed_departure || []).includes(p.name)));
                                const gkCharges = state.grave_keeper_charges !== undefined ? state.grave_keeper_charges : 2;
                                if (!assignedGk || isGkDead || gkCharges <= 0) return 'hidden';
                                return 'active';
                            }

                            const revealedHidden = state.revealed_hidden_roles || [];
                            const isRoleRevealedHidden = revealedHidden.includes(role) || gkRevealed;

                            if (role === 'Mafia') {
                                const mafiaActive = state.players.some(p => ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(p.role));
                                if (!mafiaActive) return 'hidden';
                                const mafiaAlive = state.players.some(p => ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(p.role) && p.status === 'alive' && !(state.delayed_departure || []).includes(p.name));
                                if (mafiaAlive) return 'active';
                                return isRoleRevealedHidden ? 'hidden' : 'dead';
                            }

                            const roleHolders = state.players.filter(p => p.role === role);
                            if (roleHolders.length === 0) return 'hidden';

                            const isAlive = roleHolders.some(p => p.status === 'alive' && !(state.delayed_departure || []).includes(p.name));
                            if (isAlive) return 'active';

                            return isRoleRevealedHidden ? 'hidden' : 'dead';
                        }

                        function shouldCallRole(role, state, gkRevealed) {
                            const status = getRoleStatus(role, state, gkRevealed);
                            if (status === 'active') return true;  // call during night
                            if (status === 'dead') return true;    // call during night (action disabled)
                            if (status === 'hidden') return false; // don't call during night
                            return false;
                        }

                        html += `
                            <div class="bg-indigo-950/60 border-2 border-indigo-500/60 p-6 rounded-xl space-y-5 shadow-2xl">
                                <div class="flex flex-col sm:flex-row justify-between items-center border-b border-indigo-900/80 pb-3 gap-3">
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-widest text-indigo-400">${__('night_control_center')}</span>
                                        <h2 class="text-xl font-black text-white mt-0.5">${__('call_roles_record_actions')}</h2>
                                    </div>
                                    <button type="button" onclick="localNextPhase()" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-bold text-xs uppercase tracking-wider shadow transition text-center">
                                        ${__('end_night_start_day')}
                                    </button>
                                </div>

                                <p class="text-xs text-amber-300 bg-amber-950/40 border border-amber-900/60 p-3 rounded-lg">
                                    ⚠️ <strong>${__('host_calling_rule')}</strong>
                                    ${gkRevealed ? __('gk_skip_calling_rule') : __('gk_call_all_rule')}
                                </p>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        `;

                        const assignedRoles = {};
                        state.players.forEach(p => {
                            if (p.role && p.role !== 'Pending' && p.role !== 'Citizen') {
                                assignedRoles[p.role] = p.name;
                            }
                        });

                        const nightRoles = ['Grave Keeper', 'Mafia', 'Deceiver', 'Mafia Doctor', 'Police', 'Town Doctor', 'Investigator', 'Suicidal Bomb'];

                        nightRoles.forEach(role => {
                            if (!shouldCallRole(role, state, gkRevealed)) return;

                            const status = getRoleStatus(role, state, gkRevealed);
                            const isRoleHolderDead = (status === 'dead');

                            const recordedTargetId = state.night_actions[role] || null;
                            const recordedTargetPlayer = state.players.find(p => p.id === recordedTargetId);
                            const recordedTarget = recordedTargetPlayer ? recordedTargetPlayer.name : (recordedTargetId === 'none' ? __('none_no_selection') : (recordedTargetId || null));

                            let statusText = recordedTarget ? __('recorded') : __('pending');
                            let badgeClass = recordedTarget ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-amber-950 text-amber-400 border border-amber-800';

                            if (role === 'Grave Keeper') {
                                statusText = state.grave_keeper_acted_tonight ? __('decided') : __('host_prompt');
                                badgeClass = state.grave_keeper_acted_tonight ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-indigo-950 text-indigo-400 border border-indigo-800';
                            } else if (isRoleHolderDead) {
                                statusText = __('inactive');
                                badgeClass = 'bg-slate-800 text-slate-400 border border-slate-700';
                            }

                            let promptDesc = '';
                            if (role === 'Grave Keeper') {
                                promptDesc = state.grave_keeper_acted_tonight ? __('gk_already_decided') : __('select_grave_keeper_action');
                            } else if (role === 'Mafia') {
                                promptDesc = __('select_mafia_boss_target');
                            } else if (role === 'Deceiver') {
                                promptDesc = __('select_deceiver_target');
                            } else if (role === 'Mafia Doctor') {
                                promptDesc = __('select_mafia_doc_target');
                            } else if (role === 'Police') {
                                promptDesc = __('select_police_target');
                            } else if (role === 'Town Doctor') {
                                promptDesc = __('select_town_doc_target').replace('%s', state.town_doctor_self_protect_count || 0);
                            } else if (role === 'Investigator') {
                                promptDesc = __('select_investigator_target');
                            } else if (role === 'Suicidal Bomb') {
                                promptDesc = __('select_suicidal_bomb_target');
                            }

                            html += `
                                <div class="bg-slate-900 border border-slate-800 p-4 rounded-xl flex flex-col justify-between space-y-4" data-role-card="${role}">
                                    <div>
                                        <div class="flex justify-between items-center">
                                            <span class="font-black text-sm text-rose-400">
                                                ${role === 'Grave Keeper' ? `${getRoleLabel('Grave Keeper')} (${__('charges_left')} ${gkCharges}/2)` : getRoleLabel(role)}
                                            </span>
                                            <span class="status-badge text-[10px] px-2 py-0.5 rounded font-bold uppercase ${badgeClass}">
                                                ${statusText}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">${promptDesc}</p>
                                    </div>

                                    <div class="space-y-2">
                            `;

                            if (role === 'Grave Keeper') {
                                if (state.grave_keeper_acted_tonight) {
                                    html += `
                                        <div class="text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center">
                                            ${__('gk_decision_recorded')}
                                        </div>
                                    `;
                                } else {
                                    html += `
                                        <select id="gk-select" class="w-full bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded p-2 focus:outline-none focus:border-rose-500">
                                            <option value="">${__('make_selection')}</option>
                                            <option value="yes">${__('gk_option_yes')}</option>
                                            <option value="no">${__('gk_option_no')}</option>
                                        </select>
                                        <button type="button" onclick="confirmGkAction()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 rounded uppercase tracking-wider transition shadow">
                                            ${__('confirm_decision')}
                                        </button>
                                    `;
                                }
                            } else if (isRoleHolderDead) {
                                html += `
                                    <div class="text-xs text-rose-400 bg-rose-950/40 p-2.5 rounded border border-rose-900/60 text-center font-bold italic">
                                        🚫 ${__('role_holder_eliminated')}
                                    </div>
                                `;
                            } else {
                                html += `
                                    <select id="select-${role}" class="target-select w-full bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded p-2 focus:outline-none focus:border-rose-500">
                                        <option value="none" ${recordedTargetId === 'none' ? 'selected' : ''}>${__('none_no_selection')}</option>
                                `;

                                state.players.forEach(p => {
                                    if (p.status !== 'alive') return;

                                    if (role === 'Mafia Doctor') {
                                        const isMaf = ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(p.role);
                                        if (!isMaf) return;
                                    }

                                    if (role === 'Police' && p.role === 'Police') return;
                                    if (role === 'Town Doctor' && p.role === 'Town Doctor' && (state.town_doctor_self_protect_count || 0) >= 2) return;
                                    if (role === 'Deceiver' && p.role === 'Mafia Boss') return;

                                    const isSelected = (recordedTargetId === p.id || recordedTargetId === p.name) ? 'selected' : '';
                                    html += `<option value="${p.id}" ${isSelected}>${p.name}</option>`;
                                });

                                let selectedLabel = recordedTarget ? `${__('selected')} ${recordedTarget}` : '';
                                if (recordedTargetId === 'none') {
                                    selectedLabel = `${__('selected')} ${__('none_no_selection')}`;
                                } else if (recordedTarget && role === 'Investigator') {
                                    const p = state.players.find(pl => String(pl.id) === String(recordedTargetId) || pl.name === recordedTarget);
                                    if (p) {
                                        const targetRole = p.role || 'Citizen';
                                        let evalRes = 'Citizen';
                                        if (targetRole === 'Mafia Boss') {
                                            evalRes = 'Citizen';
                                        } else {
                                            const deceiverAlive = state.players.some(pl => pl.role === 'Deceiver' && pl.status === 'alive');
                                            const deceiverTarget = state.night_actions ? state.night_actions['Deceiver'] : null;
                                            let isDeceived = false;
                                            if (deceiverAlive && deceiverTarget) {
                                                const pDeceiver = state.players.find(pl => String(pl.id) === String(deceiverTarget) || pl.name === deceiverTarget);
                                                if (pDeceiver && pDeceiver.id === p.id) {
                                                    isDeceived = true;
                                                }
                                            }
                                            const isMafia = ['Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(targetRole);
                                            if (isDeceived) {
                                                evalRes = isMafia ? 'Citizen' : 'Regular Mafia';
                                            } else {
                                                evalRes = isMafia ? 'Regular Mafia' : 'Citizen';
                                            }
                                        }
                                        const isMafiaAligned = ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(evalRes);
                                        const displayLabel = isMafiaAligned ? (i18nRoles['Mafia'] || 'Mafia') : (i18nRoles['Citizen'] || 'Citizen');
                                        selectedLabel = `${__('selected')} ${recordedTarget}: ${displayLabel}`;
                                    }
                                }

                                html += `
                                    </select>
                                    <div class="flex gap-2 buttons-container ${recordedTarget ? 'hidden' : ''}">
                                        <button type="button" onclick="confirmNightAction('${role}')" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 rounded uppercase tracking-wider transition shadow">
                                            ${__('confirm')}
                                        </button>
                                        <button type="button" onclick="cancelNightAction('${role}')" class="cancel-btn bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-800 font-bold text-xs px-3 py-2 rounded uppercase tracking-wider transition hidden" title="${__('cancel')}">
                                            ${__('cancel')}
                                        </button>
                                    </div>
                                    <div class="result-container space-y-1 ${recordedTarget ? '' : 'hidden'}">
                                        <div class="selected-text text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center truncate">
                                            ${selectedLabel}
                                        </div>
                                    </div>
                                `;
                            }

                            html += `
                                    </div>
                                </div>
                            `;
                        });

                        html += `
                                </div>
                            </div>
                        `;
                    }

                    // 3. DAYBREAK REPORT BANNER
                    if (state.phase === 'day' && state.last_night_report) {
                        const report = state.last_night_report;
                        const killedList = report.killed_names || [];

                        let sentence = '';
                        const escapedNames = killedList.map(n => `<span class="text-white underline font-black">${n}</span>`);
                        const lang = translations.lang_code || 'ku';

                        if (escapedNames.length === 0) {
                            sentence = `<span class="text-emerald-400 font-bold text-base">${__('no_players_leaving')}</span>`;
                        } else {
                            if (lang === 'ku') {
                                if (escapedNames.length === 1) {
                                    sentence = escapedNames[0] + ' دێ ژ یاریێ دەرکەڤیت.';
                                } else {
                                    const last = escapedNames.pop();
                                    sentence = escapedNames.join(' و ') + ' و ' + last + ' دێ ژ یاریێ دەرکەڤن.';
                                }
                            } else if (lang === 'ar') {
                                if (escapedNames.length === 1) {
                                    sentence = escapedNames[0] + ' سيغادر اللعبة.';
                                } else {
                                    const last = escapedNames.pop();
                                    sentence = escapedNames.join(' و ') + ' و ' + last + ' سيغادرون اللعبة.';
                                }
                            } else {
                                if (escapedNames.length === 1) {
                                    sentence = escapedNames[0] + ' will leave the game.';
                                } else {
                                    const last = escapedNames.pop();
                                    sentence = escapedNames.join(', ') + ' and ' + last + ' will leave the game.';
                                }
                            }
                            sentence = `<span class="text-rose-400 font-black text-base leading-relaxed">⚠️ ${sentence}</span>`;
                        }

                        html += `
                            <div class="bg-slate-900 border-2 border-rose-500/50 p-6 rounded-xl space-y-3 shadow-2xl">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">☀️</span>
                                    <h2 class="text-lg font-black uppercase text-rose-400">${__('day_morning_report').replace('%d', state.day)}</h2>
                                </div>

                                <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 space-y-4 text-sm">
                                    <div>${sentence}</div>

                                    <div class="border-t border-slate-800 pt-3 mt-3 space-y-2">
                                        <p class="text-xs ${state.grave_keeper_revealed_roles ? 'text-emerald-400' : 'text-slate-400'} font-bold">
                                            ${state.grave_keeper_revealed_roles 
                                                ? (lang === 'ku' ? '🪦 بڕیارا گورکولی: (بەلێ) - ڕۆلێن یاریزانێن دەرکەفتین و مرین هاتنە ئاشکراکرن:' : (lang === 'ar' ? '🪦 قرار حارس القبور: (نعم) - تم كشف أدوار اللاعبين المطرودين والمستبعدين:' : '🪦 Gravedigger Decision: (YES) - Revealed roles of voted out and eliminated players:'))
                                                : (lang === 'ku' ? '🪦 بڕیارا گورکولی: (نەخێر) - ڕۆلێن مرین هاتنە ڤەشارتن.' : (lang === 'ar' ? '🪦 قرار حارس القبور: (لا) - تم إبقاء أدوار الموتى مخفية.' : '🪦 Gravedigger Decision: (NO) - Roles remain hidden.'))
                                            }
                                        </p>
                        `;

                        if (state.grave_keeper_revealed_roles) {
                            const revealedRoles = report.revealed_roles || {};
                            let badgesHtml = '';
                            Object.keys(revealedRoles).forEach(k => {
                                const rName = revealedRoles[k];
                                badgesHtml += `<span class="inline-block bg-rose-950/80 text-rose-300 border border-rose-800/80 px-2.5 py-1 rounded text-xs font-bold shadow-sm">${k} (${getRoleLabel(rName)})</span>`;
                            });

                            html += `
                                <div class="mt-1 bg-indigo-950/40 border border-indigo-900/60 p-3 rounded-lg font-bold flex flex-wrap gap-1.5">
                                    ${badgesHtml || `<span class="text-xs text-slate-500 italic">${__('no_players_eliminated_yet')}</span>`}
                                </div>
                            `;
                        }

                        html += `
                                    </div>
                                    <p class="text-[11px] text-slate-500 italic">${__('read_phrase_notice')}</p>
                                </div>
                            </div>
                        `;
                    }

                    // 4. SUICIDAL BOMB REVENGE CONTROL
                    if (state.phase === 'day' && state.suicidal_bomb_triggered_by) {
                        html += `
                            <div class="bg-rose-950/80 border-2 border-rose-500/80 p-5 rounded-xl space-y-4 shadow-xl">
                                <div class="flex items-center justify-between border-b border-rose-900/80 pb-3">
                                    <h3 class="text-sm font-black uppercase text-rose-300 tracking-wider flex items-center gap-2">
                                        <span>💣</span> ${__('suicidal_bomb_panel_title')}
                                    </h3>
                                    <span class="bg-rose-900/80 border border-rose-700 text-rose-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                                        💣 ${getRoleLabel('Suicidal Bomb')}: ${state.suicidal_bomb_triggered_by}
                                    </span>
                                </div>

                                <div class="bg-slate-900 p-4 rounded-lg border border-slate-800 space-y-3">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-rose-200">
                                            ${__('suicidal_bomb_choose_target')}
                                        </label>
                                        <select id="bomb-revenge-select" class="w-full bg-slate-950 border border-rose-700/80 text-slate-200 text-xs rounded-lg p-2.5 focus:outline-none focus:border-rose-500">
                                            <option value="">${__('make_selection')}</option>
                                            <option value="none" class="text-emerald-400 font-bold">${__('suicidal_bomb_option_none')}</option>
                        `;

                        state.players.forEach(p => {
                            if (p.status === 'alive') {
                                html += `<option value="${p.id}">${p.name} (${getRoleLabel(p.role)})</option>`;
                            }
                        });

                        html += `
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                        <button type="button" onclick="confirmBombExplosion()" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-2.5 rounded-lg uppercase tracking-wider transition shadow">
                                            ${__('suicidal_bomb_explode_btn')}
                                        </button>
                                        <button type="button" onclick="leaveBombAlone()" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs py-2.5 rounded-lg uppercase tracking-wider transition shadow">
                                            ${__('suicidal_bomb_leave_alone_btn')}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    // 5. HOST CONTROL PANEL (2 Columns)
                    const lang = translations.lang_code || 'ku';
                    html += `
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Left Column: Players Table -->
                            <div class="md:col-span-2 bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-6 shadow-lg">
                                <div class="flex justify-between items-center">
                                    <h2 class="text-lg font-bold text-slate-200">${__('lobby_and_players')}</h2>
                                    <div class="text-xs font-bold bg-slate-800 px-3 py-1.5 rounded border border-slate-700 text-sky-400">
                                        ${__('connected_players')} <span id="online-count">${state.players.length}</span>
                                    </div>
                                </div>

                                <!-- Grave Keeper Status Info Box -->
                                <div class="bg-slate-950 p-4 rounded-lg border border-indigo-900/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div>
                                        <span class="text-xs font-bold text-indigo-400 uppercase block">${__('gk_status_reveal_state')}</span>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            ${__('charges_left')} <strong class="text-white">${state.grave_keeper_charges || 0}/2</strong> |
                                            <strong class="${state.grave_keeper_revealed_roles ? 'text-emerald-400' : 'text-rose-400'}">
                                                ${state.grave_keeper_revealed_roles ? __('revealed_status_yes') : __('revealed_status_no')}
                                            </strong>
                                        </p>
                                    </div>
                                </div>

                                <!-- Confidential Log Block -->
                                <div id="confidential-log-block" class="bg-slate-950 p-4 rounded-lg border border-slate-800 space-y-2">
                                    <details class="group" open>
                                        <summary class="flex justify-between items-center font-bold text-xs uppercase tracking-wider text-emerald-400 cursor-pointer select-none">
                                            <span>🕵️ Host Only Confidential Operations Log</span>
                                            <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                                        </summary>
                                        <div class="pt-3 space-y-2 text-xs leading-relaxed text-slate-300 font-medium">
                    `;

                    if (state.last_night_report && state.last_night_report.diary_entries && state.last_night_report.diary_entries.length > 0) {
                        state.last_night_report.diary_entries.forEach(entry => {
                            const entryText = entry[lang] || entry['en'] || '';
                            html += `<div class="border-b border-slate-900 pb-1.5">${entryText}</div>`;
                        });
                    } else {
                        html += `<div class="text-slate-500 italic">No confidential night actions recorded yet for this session.</div>`;
                    }

                    html += `
                                        </div>
                                    </details>
                                </div>

                                <!-- Players Table -->
                                <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950">
                                    <table class="w-full text-left rtl:text-right border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-slate-900 border-b border-slate-800 text-slate-400 font-bold uppercase tracking-wider">
                                                <th class="p-3.5">${__('player_name')}</th>
                                                <th class="p-3.5">${__('assigned_role')}</th>
                                                <th class="p-3.5">${__('status')}</th>
                                                <th class="p-3.5 text-right rtl:text-left">${__('actions')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    state.players.forEach(p => {
                        const isDelayed = state.delayed_departure.includes(p.name);
                        const statusText = isDelayed ? __('alive_temporarily_mirhas') : (p.status === 'alive' ? __('alive') : __('dead'));
                        const statusClass = (p.status === 'alive' || isDelayed) ? 'text-emerald-400' : 'text-rose-500 line-through';

                        let kickButton = '';
                        if (state.phase === 'day' && p.status === 'alive' && !isDelayed) {
                            kickButton = `
                                <button type="button" onclick="localLynchPlayer('${p.id}')" class="text-xs text-amber-300 hover:text-white bg-amber-950/60 px-2.5 py-1 rounded border border-amber-800">
                                    ${__('vote_kick_daytime')}
                                </button>
                            `;
                        }

                        html += `
                            <tr class="hover:bg-slate-800/50 border-b border-slate-900/50">
                                <td class="p-3 font-semibold text-slate-200">${p.name}</td>
                                <td class="p-3">
                                    <span class="px-2.5 py-1 rounded text-[11px] font-bold bg-slate-800 text-sky-300 border border-slate-700">
                                        ${getRoleLabel(p.role)}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="text-xs font-bold ${statusClass}">${statusText}</span>
                                </td>
                                <td class="p-3 text-right rtl:text-left space-x-2 flex flex-wrap justify-end gap-1">
                                    ${kickButton}
                                    <button type="button" onclick="localToggleStatus('${p.id}')" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2.5 py-1 rounded border border-slate-700">
                                        ${__('toggle_alive_dead')}
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Right Column: Sidebar -->
                            <div class="space-y-6">
                                <!-- Phase Management Sidebar -->
                                <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-4 shadow-lg">
                                    <h2 class="text-base font-bold text-slate-200 border-b border-slate-850 pb-2.5">${__('phase_management')}</h2>
                                    <div class="space-y-3">
                                        <div class="bg-slate-950 p-3 rounded-lg border border-slate-850 flex items-center justify-between">
                                            <span class="text-xs text-slate-400 uppercase font-bold">${__('current_phase')}</span>
                                            <span class="text-xs font-black uppercase text-rose-400 bg-rose-950/60 px-2.5 py-1 rounded border border-rose-900/40">
                                                ${state.phase === 'setup' ? __('phase_setup') : (state.phase === 'night' ? __('phase_night') : __('phase_day'))} ${state.day}
                                            </span>
                                        </div>

                                        <button type="button" onclick="localNextPhase()" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 rounded-lg text-xs uppercase tracking-wider shadow transition">
                                            ${__('go_to_next_phase')}
                                        </button>
                                    </div>

                                    <div class="pt-3 border-t border-slate-850 flex justify-end">
                                        <form method="POST" id="reset-form" class="inline">
                                            <input type="hidden" name="action" value="hard_reset">
                                            <button type="submit" class="text-xs text-rose-400 hover:underline bg-slate-800 px-3 py-1.5 rounded border border-slate-700">
                                                ${__('reset_session')}
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Logs List Card -->
                                <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl space-y-4 shadow-lg">
                                    <div class="flex justify-between items-center border-b border-slate-850 pb-2.5">
                                        <h2 class="text-base font-bold text-slate-200">📜 Game Timeline & Logs</h2>
                                    </div>
                                    <div id="logs-container" class="bg-slate-950 p-3 rounded-lg border border-slate-850 text-[11px] leading-relaxed text-slate-400 space-y-2 max-h-80 overflow-y-auto">
                    `;

                    [...state.logs].reverse().forEach(log => {
                        html += `<div class="border-b border-slate-900 pb-1.5">${log}</div>`;
                    });

                    html += `
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    clientContainer.innerHTML = html;
                }

                // Append GameStateManager initialization to DOMContentLoaded
                document.addEventListener('DOMContentLoaded', () => {
                    if (window.isRolesShared) {
                        const mainContainer = document.getElementById('main-container');
                        if (mainContainer) {
                            let clientContainer = document.getElementById('client-gameplay-container');
                            if (!clientContainer) {
                                clientContainer = document.createElement('div');
                                clientContainer.id = 'client-gameplay-container';
                                clientContainer.className = 'space-y-6';
                                
                                const children = Array.from(mainContainer.children);
                                children.forEach((child, index) => {
                                    if (index > 3) {
                                        child.style.display = 'none';
                                    }
                                });
                                mainContainer.appendChild(clientContainer);
                            }
                        }

                        let state = getGameState();
                        if (!state || state.reset_token !== window.serverResetToken) {
                            state = initializeGameState(window.serverPlayers, window.serverResetToken);
                        }
                        renderHostUI();
                    }
                });

                document.addEventListener('submit', function(e) {
                    if (e.target && (e.target.id === 'rematch-form' || e.target.id === 'reset-form' || e.target.querySelector('input[value="hide_roles"]') || e.target.querySelector('input[value="hard_reset"]'))) {
                        localStorage.removeItem('mafia_game_state');
                    }
                });

                
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
                                // Click sound disabled per user request
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
                }

                function updateMuteUI() {
                    const isMuted = localStorage.getItem('mafia_sound_muted') === 'true';
                    const muteIcon = document.getElementById('mute-icon');
                    const muteText = document.getElementById('mute-text');
                    const isKu = window.currentLang === "ku";
                    const isAr = window.currentLang === "ar";
                    
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
                    pauseTimer();
                    saveRemainingSeconds(defaultDuration);
                    updateTimerDisplay();
                }

                function setTimerPreset(sec) {
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

                // ON LOAD BOOTSTRAP FOR AUDIO AND TIMER
                document.addEventListener('DOMContentLoaded', () => {
                    updateMuteUI();
                    checkAndRecoverTimer();

                    if (window.isRolesShared) {
                        renderHostUI();
                    }

                    setInterval(pollHost, 3000);
                });

                let lastRolesSharedState = window.isRolesShared;
                let lastResetTokenState = window.serverResetToken;

                function getPhaseLabel(phase, day, winner) {
                    if (winner) return i18nTxt.gameOver;
                    let pName = phase === 'setup' ? i18nTxt.phaseSetup : (phase === 'night' ? i18nTxt.phaseNight : i18nTxt.phaseDay);
                    return pName + (phase !== 'setup' ? ' ' + day : '');
                }

                function pollHost() {
                    fetch('actions.php?ajax=1')
                        .then(r => r.json())
                        .then(data => {
                            if (data.roles_shared !== lastRolesSharedState || (data.reset_token && data.reset_token !== lastResetTokenState)) {
                                if (!data.roles_shared) {
                                    localStorage.removeItem('mafia_game_state');
                                }
                                window.location.reload();
                                return;
                            }

                            const onlineCountEl = document.getElementById('online-count');
                            if (onlineCountEl && data.players) {
                                onlineCountEl.innerText = data.players.length;
                            }
                        })
                        .catch(err => console.error("Poll error:", err));
                }

                // Background polling removed per user request
                // function pollHost is still available for manual/form updates if needed

                // Helper to save action locally and show save feedback on button
                function saveLocalNightAction(role, targetId, form) {
                    if (!window.localNightActions) window.localNightActions = {};
                    if (targetId) {
                        window.localNightActions[role] = targetId;
                    } else {
                        delete window.localNightActions[role];
                    }

                    if (form) {
                        const btn = form.querySelector('button[type="submit"]');
                        if (btn) {
                            const originalText = btn.innerText;
                            btn.innerText = '✅ Saved';
                            setTimeout(() => btn.innerText = originalText, 1500);
                        }
                    }
                }

                // Basic UI update helper
                function updateNightCardBasicUI(card, targetName) {
                    if (!card) return;
                    const statusBadge = card.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = "status-badge text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800 px-2 py-0.5 rounded font-bold uppercase";
                        statusBadge.innerText = i18nTxt.recorded || 'Recorded';
                    }
                    
                    const buttonsContainer = card.querySelector('.buttons-container');
                    if (buttonsContainer) {
                        buttonsContainer.classList.add('hidden');
                    }
                    
                    const cancelBtn = card.querySelector('.cancel-btn');
                    if (cancelBtn) cancelBtn.classList.add('hidden');
                    
                    const resultContainer = card.querySelector('.result-container');
                    if (resultContainer) {
                        resultContainer.classList.remove('hidden');
                    }
                    
                    const selectedText = card.querySelector('.selected-text');
                    if (selectedText && targetName) {
                        const role = card.getAttribute('data-role-card');
                        if (role !== 'Investigator') {
                            const label = (targetName === 'none' || targetName === i18nTxt.noneNoSelection) ? (i18nTxt.noneNoSelection || '-- None / No Selection --') : targetName.trim();
                            selectedText.innerText = (i18nTxt.selectedPrefix || 'Selected: ') + " " + label;
                        }
                    }
                }

                // Unique function for Mafia Group night action (group shoot)
                function handleMafiaGroupNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                }

                // Unique function for Mafia Doctor night action
                function handleMafiaDoctorNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                }

                // Unique function for Deceiver night action
                function handleDeceiverNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                }

                // Unique function for Regular Mafia night action
                function handleRegularMafiaNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                }

                // Unique function for Police night action
                function handlePoliceNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                }

                // Unique function for Town Doctor night action
                function handleTownDoctorNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                }

                // Unique function for Investigator night action
                function handleInvestigatorNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                    
                    const resultContainer = card.querySelector('.result-container');
                    if (!resultContainer) return;
                    resultContainer.classList.remove('hidden');
                    
                    const invRes = resultContainer.querySelector('.investigator-result');
                    if (invRes) {
                        invRes.classList.remove('hidden');
                        
                        // Get the state from local storage database
                        const state = getGameState();
                        if (state) {
                            const p = state.players.find(pl => String(pl.id) === String(targetId));
                            if (p) {
                                const targetRole = p.role || 'Citizen';
                                
                                let evalRes = 'Citizen';
                                if (targetRole === 'Mafia Boss') {
                                    evalRes = 'Citizen';
                                } else {
                                    // Check if Deceiver is alive in game
                                    const deceiverAlive = state.players.some(pl => pl.role === 'Deceiver' && pl.status === 'alive');
                                    
                                    // Check if target is deceived by Deceiver tonight
                                    const localDeceiverTarget = window.localNightActions ? window.localNightActions['Deceiver'] : null;
                                    const stateDeceiverTarget = state.night_actions ? state.night_actions['Deceiver'] : null;
                                    const deceiverTarget = localDeceiverTarget || stateDeceiverTarget;
                                    
                                    let isDeceived = false;
                                    if (deceiverAlive && deceiverTarget) {
                                        const pDeceiver = state.players.find(pl => String(pl.id) === String(deceiverTarget) || pl.name === deceiverTarget);
                                        if (pDeceiver && pDeceiver.id === p.id) {
                                            isDeceived = true;
                                        }
                                    }
                                    
                                    const isMafia = ['Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(targetRole);
                                    if (isDeceived) {
                                        evalRes = isMafia ? 'Citizen' : 'Regular Mafia';
                                    } else {
                                        evalRes = isMafia ? 'Regular Mafia' : 'Citizen';
                                    }
                                }
                                
                                const isMafiaAligned = ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(evalRes);
                                const displayLabel = isMafiaAligned ? (i18nRoles['Mafia'] || 'Mafia') : (i18nRoles['Citizen'] || 'Citizen');

                                if (isMafiaAligned) {
                                    invRes.className = 'investigator-result text-xs font-bold p-2 rounded border text-center bg-rose-950/80 border-rose-800 text-rose-300 animate-pulse mt-1';
                                    invRes.innerHTML = `${i18nTxt.investigatorResult || '🔍 Investigator Result:'} <span class="underline uppercase font-extrabold text-sm">${displayLabel}</span>`;
                                } else {
                                    invRes.className = 'investigator-result text-xs font-bold p-2 rounded border text-center bg-sky-950/80 border-sky-800 text-sky-300 mt-1';
                                    invRes.innerHTML = `${i18nTxt.investigatorResult || '🔍 Investigator Result:'} <span class="underline uppercase font-extrabold text-sm">${displayLabel}</span>`;
                                }

                                const selectedText = card.querySelector('.selected-text');
                                if (selectedText) {
                                    selectedText.innerText = (i18nTxt.selectedPrefix || 'Selected: ') + " " + targetName.trim() + ": " + displayLabel;
                                }

                                // Display actual exact role as requested by the user
                                let invActualRes = resultContainer.querySelector('.investigator-actual-role');
                                if (!invActualRes) {
                                    invActualRes = document.createElement('div');
                                    invActualRes.className = 'investigator-actual-role text-xs font-bold p-2 rounded border text-center bg-indigo-950/80 border-indigo-800 text-indigo-300 mt-1';
                                    resultContainer.appendChild(invActualRes);
                                }
                                invActualRes.classList.remove('hidden');
                                const targetRoleTranslated = i18nRoles[targetRole] || targetRole;
                                const exactRoleLabel = window.currentLang === 'ku' ? 'رۆلێ دروست یێ یاریزانێ هاتیە دیارکرن:' : (window.currentLang === 'ar' ? 'دور اللاعب الحقيقي الكاشف:' : 'Target’s Exact Role:');
                                invActualRes.innerHTML = `${exactRoleLabel} <span class="underline uppercase font-extrabold text-sm">${targetRoleTranslated}</span>`;
                            }
                        }
                    }
                }

                // Unique function for Suicidal Bomb night action
                function handleSuicidalBombNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                }

                // Unique function for Grave Keeper night action
                function handleGraveKeeperNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    if (targetId === '') return;
                    
                    saveLocalNightAction(role, targetId, form);

                    if (targetId === 'yes') {
                        let state = getGameState();
                        if (state) {
                            state.grave_keeper_revealed_roles = true;
                            if (!Array.isArray(state.revealed_hidden_roles)) state.revealed_hidden_roles = [];
                            state.players.forEach(p => {
                                if (p.status === 'dead' || (state.delayed_departure || []).includes(p.name)) {
                                    if (p.role && !state.revealed_hidden_roles.includes(p.role)) {
                                        state.revealed_hidden_roles.push(p.role);
                                    }
                                }
                            });
                            saveGameState(state);
                        }
                        try {
                            localStorage.setItem('grave_keeper_revealed_roles', 'true');
                            localStorage.setItem('mafia_gk_revealed', 'true');
                        } catch(e) {}
                    } else {
                        try {
                            localStorage.removeItem('grave_keeper_revealed_roles');
                            localStorage.removeItem('mafia_gk_revealed');
                        } catch(e) {}
                    }
                    
                    const gkButtons = card.querySelector('#gk-buttons-container');
                    if (gkButtons) gkButtons.classList.add('hidden');
                    
                    const statusBadge = card.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = "status-badge text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800 px-2 py-0.5 rounded font-bold uppercase";
                        statusBadge.innerText = i18nTxt.decided || 'Decided';
                    }

                    const cancelBtn = card.querySelector('.cancel-btn');
                    if (cancelBtn) cancelBtn.classList.remove('hidden');
                    
                    let notice = card.querySelector('.gk-notice');
                    if (!notice) {
                        notice = document.createElement('div');
                        notice.className = "gk-notice text-xs text-emerald-400 font-bold bg-emerald-950/40 p-2 rounded border border-emerald-900 text-center mt-2 flex items-center justify-between gap-2";
                        form.appendChild(notice);
                    }
                    const label = targetId === 'yes' ? (i18nTxt.gkOptionYes || 'Reveal Roles') : (i18nTxt.gkOptionNo || 'Do Not Reveal');
                    notice.innerHTML = `<span>${i18nTxt.gkDecisionRecorded || 'Decision Recorded'}: <strong>${label}</strong></span>`;
                }

                // Unique function for Citizen night action
                function handleCitizenNightAction(card, targetSelect, role, form) {
                    const targetId = targetSelect ? targetSelect.value : '';
                    const targetName = (targetSelect && targetSelect.selectedIndex !== -1 && targetId !== '') ? targetSelect.options[targetSelect.selectedIndex].text : '';
                    
                    saveLocalNightAction(role, targetId, form);
                    if (targetId === '') {
                        cancelNightAction(role);
                        return;
                    }
                    updateNightCardBasicUI(card, targetName);
                }

                // Main Night Action Handler dispatching to dedicated role functions
                function handleNightActionSubmit(event, role) {
                    event.preventDefault();
                    const form = event.target;
                    const targetSelect = form.querySelector('.target-select');
                    const card = form.closest('.bg-slate-900');
                    
                    const roleHandlers = {
                        'Mafia': handleMafiaGroupNightAction,
                        'Mafia Group': handleMafiaGroupNightAction,
                        'Mafia Boss': handleMafiaGroupNightAction,
                        'Mafia Doctor': handleMafiaDoctorNightAction,
                        'Deceiver': handleDeceiverNightAction,
                        'Regular Mafia': handleRegularMafiaNightAction,
                        'Police': handlePoliceNightAction,
                        'Town Doctor': handleTownDoctorNightAction,
                        'Investigator': handleInvestigatorNightAction,
                        'Suicidal Bomb': handleSuicidalBombNightAction,
                        'Grave Keeper': handleGraveKeeperNightAction,
                        'Citizen': handleCitizenNightAction
                    };
                    
                    const handler = roleHandlers[role] || handleCitizenNightAction;
                    handler(card, targetSelect, role, form);
                }

                // Show confirm button when changing target selection in dropdown
                document.addEventListener('change', function(e) {
                    if (e.target && e.target.classList.contains('target-select')) {
                        const card = e.target.closest('[data-role-card]');
                        if (card) {
                            // Show confirm button container when target selection is changed
                            const buttonsContainer = card.querySelector('.buttons-container');
                            if (buttonsContainer) {
                                buttonsContainer.classList.remove('hidden');
                                // Ensure Cancel button remains hidden
                                const cancelBtn = buttonsContainer.querySelector('.cancel-btn');
                                if (cancelBtn) cancelBtn.classList.add('hidden');
                            }
                        }
                    }
                });

                function cancelNightAction(role) {
                    // Update local storage
                    if (window.localNightActions) {
                        delete window.localNightActions[role];
                    }

                    // Update UI
                    const card = document.querySelector(`[data-role-card="${role}"]`);
                    if (card) {
                        const targetDisplay = card.querySelector('.target-display');
                        if (targetDisplay) targetDisplay.innerText = '';
                        const cancelBtn = card.querySelector('.cancel-btn');
                        if (cancelBtn) cancelBtn.classList.add('hidden');
                        const targetSelect = card.querySelector('.target-select');
                        if (targetSelect) targetSelect.value = '';
                        
                        const statusBadge = card.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.className = "status-badge text-[10px] bg-amber-950 text-amber-400 border border-amber-800 px-2 py-0.5 rounded font-bold uppercase";
                            statusBadge.innerText = i18nTxt.pending || 'Pending';
                        }

                        if (role === 'Grave Keeper') {
                            const gkButtons = card.querySelector('#gk-buttons-container');
                            if (gkButtons) gkButtons.classList.remove('hidden');
                            const notice = card.querySelector('.gk-notice');
                            if (notice) notice.remove();
                            try {
                                localStorage.removeItem('grave_keeper_revealed_roles');
                                localStorage.removeItem('mafia_gk_revealed');
                            } catch(e) {}
                        }
                        
                        const resultContainer = card.querySelector('.result-container');
                        if (resultContainer) resultContainer.classList.add('hidden');
                    }
                }

                function refreshNightCardUI(role, data) {
                    if (window.localNightActions && window.localNightActions.hasOwnProperty(role)) {
                        return; // Do not overwrite local unsaved changes with server state
                    }
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
                            const buttonsContainer = card.querySelector('.buttons-container');
                            if (buttonsContainer) {
                                buttonsContainer.classList.add('hidden');
                            }
                            if (cancelBtn) cancelBtn.classList.add('hidden');
                            if (resultContainer) resultContainer.classList.remove('hidden');
                            if (selectedText) {
                                const label = (recordedTarget === 'none' || recordedTarget === i18nTxt.noneNoSelection) ? (i18nTxt.noneNoSelection || '-- None / No Selection --') : recordedTarget;
                                selectedText.innerText = (i18nTxt.selectedPrefix || 'Selected: ') + " " + label;
                            }

                            if (role === 'Investigator' && selectElem) {
                                const p = data.players.find(pl => pl.name === recordedTarget);
                                if (p) {
                                    const targetRole = p.role || 'Citizen';
                                    
                                    let evalRes = 'Citizen';
                                    if (targetRole === 'Mafia Boss') {
                                        evalRes = 'Citizen';
                                    } else {
                                        const deceiverAlive = data.players.some(pl => pl.role === 'Deceiver' && pl.status === 'alive');
                                        const deceiverTarget = data.night_actions ? data.night_actions['Deceiver'] : null;
                                        
                                        let isDeceived = false;
                                        if (deceiverAlive && deceiverTarget) {
                                            const pDeceiver = data.players.find(pl => String(pl.id) === String(deceiverTarget) || pl.name === deceiverTarget);
                                            if (pDeceiver && pDeceiver.id === p.id) {
                                                isDeceived = true;
                                            }
                                        }
                                        
                                        const isMafia = ['Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(targetRole);
                                        if (isDeceived) {
                                            evalRes = isMafia ? 'Citizen' : 'Regular Mafia';
                                        } else {
                                            evalRes = isMafia ? 'Regular Mafia' : 'Citizen';
                                        }
                                    }
                                    
                                    const isMafiaAligned = ['Mafia Boss', 'Mafia Doctor', 'Deceiver', 'Regular Mafia'].includes(evalRes);
                                    const displayLabel = isMafiaAligned ? (i18nRoles['Mafia'] || 'Mafia') : (i18nRoles['Citizen'] || 'Citizen');

                                    let invRes = resultContainer.querySelector('.investigator-result');
                                    if (invRes) {
                                        if (isMafiaAligned) {
                                            invRes.className = 'investigator-result text-xs font-bold p-2 rounded border text-center bg-rose-950/80 border-rose-800 text-rose-300 animate-pulse mt-1';
                                        } else {
                                            invRes.className = 'investigator-result text-xs font-bold p-2 rounded border text-center bg-sky-950/80 border-sky-800 text-sky-300 mt-1';
                                        }
                                        invRes.innerHTML = `${i18nTxt.investigatorResult || '🔍 Investigator Result:'} <span class="underline uppercase font-extrabold text-sm">${displayLabel}</span>`;
                                    }

                                    if (selectedText) {
                                        selectedText.innerText = (i18nTxt.selectedPrefix || 'Selected: ') + " " + recordedTarget + ": " + displayLabel;
                                    }

                                    let invActualRes = resultContainer.querySelector('.investigator-actual-role');
                                    if (!invActualRes) {
                                        invActualRes = document.createElement('div');
                                        invActualRes.className = 'investigator-actual-role text-xs font-bold p-2 rounded border text-center bg-indigo-950/80 border-indigo-800 text-indigo-300 mt-1';
                                        resultContainer.appendChild(invActualRes);
                                    }
                                    invActualRes.classList.remove('hidden');
                                    const targetRoleTranslated = i18nRoles[targetRole] || targetRole;
                                    const exactRoleLabel = window.currentLang === 'ku' ? 'رۆلێ دروست یێ یاریزانێ هاتیە دیارکرن:' : (window.currentLang === 'ar' ? 'دور اللاعب الحقيقي الكاشف:' : 'Target’s Exact Role:');
                                    invActualRes.innerHTML = `${exactRoleLabel} <span class="underline uppercase font-extrabold text-sm">${targetRoleTranslated}</span>`;
                                }
                            }
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
