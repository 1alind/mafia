// Mafia Game - Client-Side Scripts
// Helper script for Mafia Game UI
console.log("Mafia Game Client initialized");



document.addEventListener('submit', async function(e) {
    if (e.defaultPrevented) return;
    if (e.target.tagName === 'FORM') {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Add the submitter button's name/value if it exists
        if (e.submitter && e.submitter.name) {
            formData.append(e.submitter.name, e.submitter.value);
        }
        formData.append('ajax', '1');

        const btn = e.target.querySelector('button[type="submit"]');
        let originalText = '';
        if (btn) {
            originalText = btn.innerText;
            btn.innerText = '✅ Saved';
            setTimeout(() => { if (btn) btn.innerText = originalText; }, 1500);
        }

        try {
            const actionValue = formData.get('action');
            const targetUrl = (actionValue === 'record_night_target' || actionValue === 'answer_grave_keeper_reveal' || actionValue === 'next_phase' || actionValue === 'submit_all_night_actions') ? 'actions.php?ajax=1' : window.location.href;
            
            const response = await fetch(targetUrl, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                console.log('Form submitted directly to server DB');
                if (['next_phase', 'hide_roles', 'hard_reset', 'reset_session', 'share_roles', 'claim_host', 'kill_player', 'revive_player', 'change_role', 'rename_player'].includes(actionValue)) {
                    window.location.reload();
                    return;
                }

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    const role = formData.get('role');
                    if (role && typeof refreshNightCardUI === 'function') {
                        refreshNightCardUI(role, data);
                    }
                } else {
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
            }
        } catch (err) {
            console.error('Form submit error:', err);
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
                    return null;
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

                window.mafiaSoundMuted = false;

                function playSound(type) {
                    if (window.mafiaSoundMuted) return;
                    
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
                    window.mafiaSoundMuted = !window.mafiaSoundMuted;
                    updateMuteUI();
                    
                    // Activate Context on first toggle to bypass browser autoplays blocking
                    getAudioContext();
                }

                function updateMuteUI() {
                    const isMuted = !!window.mafiaSoundMuted;
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
                    if (window.mafiaTimerSeconds === undefined || window.mafiaTimerSeconds === null) return defaultDuration;
                    return parseInt(window.mafiaTimerSeconds, 10);
                }

                function saveRemainingSeconds(sec) {
                    window.mafiaTimerSeconds = sec;
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
                    let isRunning = !!window.mafiaTimerRunning;
                    if (isRunning) {
                        pauseTimer();
                    } else {
                        startTimer();
                    }
                }

                function startTimer() {
                    window.mafiaTimerRunning = true;
                    window.mafiaTimerLastSavedTime = Date.now();
                    
                    const playBtn = document.getElementById('timer-play-btn');
                    if (playBtn) playBtn.innerText = '⏸️';

                    if (timerInterval) clearInterval(timerInterval);
                    timerInterval = setInterval(() => {
                        let sec = getRemainingSeconds();
                        if (sec > 0) {
                            sec--;
                            saveRemainingSeconds(sec);
                            updateTimerDisplay();
                            window.mafiaTimerLastSavedTime = Date.now();
                            
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
                    window.mafiaTimerRunning = false;
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
                    let isRunning = !!window.mafiaTimerRunning;
                    if (isRunning) {
                        let lastSaved = parseInt(window.mafiaTimerLastSavedTime || '0', 10);
                        if (lastSaved > 0) {
                            let elapsed = Math.floor((Date.now() - lastSaved) / 1000);
                            if (elapsed > 0) {
                                let sec = getRemainingSeconds();
                                let newSec = Math.max(0, sec - elapsed);
                                saveRemainingSeconds(newSec);
                                if (newSec === 0) {
                                    window.mafiaTimerRunning = false;
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

                    // Phase Change Sound Alerts
                    const currentPhase = window.dbPhase;
                    const savedPhase = window.mafiaLastPhase;
                    if (savedPhase && savedPhase !== currentPhase) {
                        if (currentPhase === 'day') {
                            playSound('daybreak');
                        } else if (currentPhase === 'night') {
                            playSound('nightfall');
                        }
                    }
                    window.mafiaLastPhase = currentPhase;
                });
                let lastPhaseState = window.dbPhase;
                let lastWinnerState = window.dbWinner;
                let lastGraveRevealState = window.dbGraveReveal;
                let lastGraveChargesState = window.dbGraveCharges;

                let lastNightActionsState = window.dbNightActions;

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
                                lastRolesSharedState = data.roles_shared;
                                lastPhaseState = data.phase;
                                lastWinnerState = data.winner;
                                lastGraveRevealState = data.grave_keeper_revealed_roles;
                                lastGraveChargesState = data.grave_keeper_charges;

                                window.location.reload();
                                return;
                            }

                            if (JSON.stringify(data.night_actions) !== JSON.stringify(lastNightActionsState)) {
                                lastNightActionsState = data.night_actions;
                                if (typeof refreshNightCardUI === 'function') {
                                    const allRoles = ['Mafia', 'Mafia Doctor', 'Deceiver', 'Regular Mafia', 'Police', 'Town Doctor', 'Investigator', 'Grave Keeper', 'Mirhas', 'Suicidal Bomb', 'Citizen'];
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
                                        let removeLabel = window.currentLang === 'ku' ? 'ژێبرن' : (window.currentLang === 'ar' ? 'حذف' : 'Remove');
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
                            if (logsContainer) {
                                let logsHtml = '';
                                [...data.logs].reverse().forEach(log => {
                                    logsHtml += `<div class="border-b border-slate-900 pb-1">${log}</div>`;
                                });
                                logsContainer.innerHTML = logsHtml;
                            }
                        });
                }

                // Background polling removed per user request
                // function pollHost is still available for manual/form updates if needed

                // Helper to save action directly to server DB and show save feedback on button
                function saveLocalNightAction(role, targetId, form) {
                    const formData = new FormData();
                    formData.append('action', 'record_night_target');
                    formData.append('role', role);
                    formData.append('target_id', targetId || 'none');
                    formData.append('ajax', '1');

                    fetch('actions.php?ajax=1', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (typeof refreshNightCardUI === 'function') {
                            refreshNightCardUI(role, data);
                        }
                    })
                    .catch(err => console.error('Error saving night action to server DB:', err));

                    if (form) {
                        const btn = form.querySelector('button[type="submit"]');
                        if (btn) {
                            const originalText = btn.innerText;
                            btn.innerText = '✅ Saved';
                            setTimeout(() => { if (btn) btn.innerText = originalText; }, 1500);
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
                    
                    const selectElem = card.querySelector('.target-select');
                    if (selectElem) {
                        selectElem.classList.add('hidden');
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
                        // Handled on server DB
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
                    const formData = new FormData();
                    formData.append('action', 'record_night_target');
                    formData.append('role', role);
                    formData.append('target_id', '');
                    formData.append('ajax', '1');

                    fetch('actions.php?ajax=1', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (typeof refreshNightCardUI === 'function') {
                            refreshNightCardUI(role, data);
                        }
                    })
                    .catch(err => console.error('Error clearing night action on server DB:', err));
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
                        const buttonsContainer = card.querySelector('.buttons-container');

                        if (recordedTarget) {
                            if (statusBadge) {
                                statusBadge.className = "status-badge text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800 px-2 py-0.5 rounded font-bold uppercase";
                                statusBadge.innerText = i18nTxt.recorded;
                            }
                            if (selectElem) selectElem.classList.add('hidden');
                            if (buttonsContainer) buttonsContainer.classList.add('hidden');
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
                            if (selectElem) {
                                selectElem.classList.remove('hidden');
                                selectElem.value = "none";
                            }
                            if (buttonsContainer) buttonsContainer.classList.remove('hidden');
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
