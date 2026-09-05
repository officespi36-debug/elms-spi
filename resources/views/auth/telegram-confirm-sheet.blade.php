<!DOCTYPE html>
<html lang="km" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Log in to spilms.tech</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Telegram Web App SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '"Kantumruy Pro"', 'sans-serif'],
                    },
                    colors: {
                        telegramGreen: '#34c759',
                        telegramDark: '#121214',
                        telegramCard: '#1c1c1e',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0b0b0d;
            font-family: 'Plus Jakarta Sans', 'Kantumruy Pro', sans-serif;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }
        .apple-blur {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-up {
            animation: slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.15);
            border-radius: 4px;
        }
    </style>
</head>
<body class="min-h-screen w-full text-white flex flex-col justify-between p-4 sm:p-6 relative overflow-x-hidden">

    <!-- Top Navigation Header -->
    <div class="w-full flex items-center justify-between z-20 pt-1 pb-3">
        <!-- Close Button (X) -->
        <button
            type="button"
            onclick="handleClose()"
            class="w-9 h-9 rounded-full bg-zinc-800/80 hover:bg-zinc-700/80 border border-zinc-700/50 flex items-center justify-center text-zinc-400 hover:text-white transition cursor-pointer active:scale-90"
            aria-label="Close"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- User Account Selector Pill (100% Matching Image 2) -->
        <button
            id="accountSwitcherPill"
            type="button"
            onclick="openAccountSelectorModal()"
            class="h-9 sm:h-10 pl-1 pr-2.5 sm:pr-3 rounded-full bg-[#202022] hover:bg-[#2a2a2e] active:scale-95 border border-[#38383a] flex items-center gap-2 shadow-lg transition-all cursor-pointer select-none group"
            title="ចុចដើម្បីជ្រើសរើស ឬប្តូរគណនី (Click to Switch Account)"
        >
            <!-- User Avatar Circle (Displays Real Avatar matching Image 2) -->
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full overflow-hidden bg-zinc-800 ring-1 ring-white/10 shrink-0 flex items-center justify-center">
                <img
                    id="userAvatar"
                    src="{{ $currentUser['avatar'] ?? '/uploads/avatars/avatar_1_1785245469.jpg' }}"
                    alt="User Avatar"
                    class="w-full h-full object-cover"
                    onerror="this.src='/uploads/avatars/avatar_1_1785245469.jpg'"
                />
            </div>
            <!-- Vertical Up/Down Chevrons Matching Image 2 Exactly -->
            <div class="flex flex-col items-center justify-center -space-y-1 text-zinc-400 group-hover:text-zinc-200 transition">
                <svg class="w-3 h-3 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15"></polyline>
                </svg>
                <svg class="w-3 h-3 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
        </button>
    </div>

    <!-- Center Stage: App Badge, Title & Details -->
    <main class="w-full max-w-sm mx-auto flex flex-col items-center justify-center my-auto py-4">
        
        <!-- Big App Icon Shield Badge (Matching Screenshot) -->
        <div class="mb-5 relative flex items-center justify-center">
            <div class="w-24 h-24 rounded-3xl bg-gradient-to-b from-blue-500/20 to-sky-500/5 p-[2px] shadow-2xl shadow-blue-500/20 ring-1 ring-blue-500/30 flex items-center justify-center">
                <div class="w-full h-full rounded-3xl bg-[#141416] flex items-center justify-center p-3 relative overflow-hidden">
                    <!-- Subtle Glow -->
                    <div class="absolute inset-0 bg-blue-500/10 rounded-3xl blur-md pointer-events-none"></div>
                    <!-- E-LMS School Official Logo -->
                    <img
                        src="/images/logo.png"
                        alt="E-LMS"
                        class="w-16 h-16 object-contain rounded-2xl relative z-10 drop-shadow-md"
                    />
                </div>
            </div>
        </div>

        <!-- Title & Domain -->
        <div class="text-center space-y-1">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-100">
                Log in to
            </h1>
            <div class="text-2xl sm:text-[26px] font-black tracking-tight text-[#34c759]">
                spilms.tech
            </div>
            <p class="text-xs sm:text-[13px] text-zinc-400 max-w-[280px] mx-auto pt-1 leading-relaxed">
                This site will receive your name, username and profile photo.
            </p>
        </div>

        <!-- Device & Location Info Box (Matching Screenshot) -->
        <div class="w-full mt-6 mb-2 rounded-2xl bg-[#1c1c1e] border border-zinc-800/80 p-4 shadow-xl">
            <!-- Row 1: Device -->
            <div class="flex items-center justify-between text-xs sm:text-sm py-1">
                <span class="text-zinc-400 font-medium">Device</span>
                <div class="text-right">
                    <span class="text-zinc-100 font-semibold block">{{ $device ?? 'Windows' }}</span>
                    <span class="text-[11px] text-zinc-400 block">{{ $browser ?? 'Chrome 152' }}</span>
                </div>
            </div>

            <!-- Subtle Divider -->
            <div class="w-full border-t border-zinc-800/60 my-2.5"></div>

            <!-- Row 2: IP Address & Location -->
            <div class="flex items-center justify-between text-xs sm:text-sm py-1">
                <span class="text-zinc-400 font-medium">IP Address</span>
                <div class="text-right">
                    <span class="text-zinc-100 font-mono font-bold block">{{ $ip ?? '36.37.147.32' }}</span>
                    <span class="text-[11px] text-zinc-400 block">{{ $location ?? 'Cambodia' }}</span>
                </div>
            </div>
        </div>

        <!-- Footnote under Info Box -->
        <p class="text-[11px] text-zinc-500 text-center tracking-wide">
            This login attempt came from the device above.
        </p>

        <!-- Status Message Banner (Success or Error) -->
        <div id="statusBanner" class="hidden w-full mt-4 p-3 rounded-xl text-xs font-semibold text-center transition-all animate-fade-in"></div>

        <!-- Direct Telegram App Fallback Button (Visible outside Telegram or when needed) -->
        <div id="openTgBtnContainer" class="w-full mt-3">
            <a
                id="directOpenTgBtn"
                href="tg://resolve?domain={{ $botUsername ?? 'spi_elms_auth_bot' }}&start=login_{{ $token }}"
                onclick="handleOpenTelegramApp(event)"
                class="w-full py-2.5 px-4 rounded-xl bg-[#0088cc] hover:bg-[#0077b5] active:scale-95 text-white text-xs font-bold flex items-center justify-center gap-2 transition shadow-md shadow-sky-500/20 cursor-pointer select-none"
            >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.19-.08-.05-.19-.02-.27 0-.12.03-1.99 1.27-5.62 3.72-.53.37-1.01.55-1.44.54-.48-.01-1.4-.27-2.09-.49-.84-.27-1.51-.42-1.45-.88.03-.24.38-.49 1.04-.75 4.09-1.78 6.82-2.96 8.19-3.53 3.9-1.63 4.71-1.91 5.24-1.92.12 0 .37.03.54.17.14.12.18.28.2.45-.02.07-.02.18-.04.35z"/>
                </svg>
                <span>បើកក្នុង Telegram App ដើម្បី Confirm</span>
            </a>
        </div>

    </main>

    <!-- Bottom Actions (Cancel / Log In Buttons) -->
    <div class="w-full max-w-sm mx-auto flex items-center gap-3 pt-3 pb-2 z-20">
        <!-- Cancel Button -->
        <button
            type="button"
            onclick="handleClose()"
            class="flex-1 h-12 rounded-full bg-[#27272a] hover:bg-[#3f3f46] text-zinc-200 hover:text-white font-semibold text-sm transition cursor-pointer active:scale-95 border border-zinc-700/50 select-none"
        >
            Cancel
        </button>

        <!-- Log In Button (Green) -->
        <button
            id="loginBtn"
            type="button"
            onclick="submitLoginApproval()"
            class="flex-1 h-12 rounded-full bg-[#34c759] hover:bg-[#2fb350] active:bg-[#289e46] text-black font-bold text-sm sm:text-[15px] transition cursor-pointer active:scale-95 shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2 select-none"
        >
            <span id="loginBtnSpinner" class="hidden">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-black" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </span>
            <span id="loginBtnText">Log In</span>
        </button>
    </div>

    <!-- ═══════════════ ACCOUNT SELECTOR MODAL (Matching Image 2 Feature) ═══════════════ -->
    <div id="accountModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/75 backdrop-blur-sm p-3 sm:p-4 transition-all duration-200">
        <div class="w-full max-w-sm rounded-3xl bg-[#1c1c1e] border border-zinc-800 p-5 shadow-2xl space-y-4 animate-slide-up select-none">
            <!-- Header -->
            <div class="flex items-center justify-between pb-2 border-b border-zinc-800/80">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <div>
                        <h3 class="text-sm font-bold text-zinc-100">ជ្រើសរើសគណនី (Choose Account)</h3>
                        <p class="text-[11px] text-zinc-400">ជ្រើសរើសគណនីដើម្បី Login លើកុំព្យូទ័រ</p>
                    </div>
                </div>
                <button
                    type="button"
                    onclick="closeAccountSelectorModal()"
                    class="w-7 h-7 rounded-full bg-zinc-800 text-zinc-400 hover:text-white flex items-center justify-center transition cursor-pointer"
                >
                    ✕
                </button>
            </div>

            <!-- List of Available Accounts to choose from -->
            <div class="space-y-1.5 pt-1">
                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400">គណនីដែលអាចជ្រើសរើសបាន៖</span>
                <div id="accountsListContainer" class="space-y-2 max-h-56 overflow-y-auto custom-scrollbar pr-0.5">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Switch / Add Account by Identifier -->
            <div class="space-y-2 pt-2 border-t border-zinc-800/80">
                <label class="text-[11px] font-semibold text-zinc-400 flex items-center justify-between">
                    <span>ចូលគណនីផ្សេង ឬវាយបញ្ចូលផ្ទាល់៖</span>
                    <span class="text-emerald-400 text-[10px]">រហ័ស 100%</span>
                </label>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        id="customIdentifierInput"
                        placeholder="លេខទូរស័ព្ទ / Student ID / Username"
                        class="flex-1 bg-zinc-950 border border-zinc-700/80 rounded-xl px-3 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-emerald-500 transition"
                        onkeydown="if(event.key === 'Enter') lookupOrSwitchAccount()"
                    />
                    <button
                        type="button"
                        id="verifySwitchBtn"
                        onclick="lookupOrSwitchAccount()"
                        class="px-3.5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 active:scale-95 text-black font-bold text-xs transition cursor-pointer shrink-0 select-none shadow-md shadow-emerald-500/20"
                    >
                        ស្វែងរក
                    </button>
                </div>
                <p id="accountLookupFeedback" class="hidden text-[11px] font-medium pt-1"></p>
            </div>

            <!-- Fast Action Buttons -->
            <div class="pt-2 border-t border-zinc-800/80 flex flex-col gap-2">
                <button
                    type="button"
                    onclick="closeAccountSelectorModal()"
                    class="w-full py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 hover:text-white text-xs font-semibold transition cursor-pointer"
                >
                    យល់ព្រម (Done)
                </button>
            </div>
        </div>
    </div>

    <script>
        const qrToken = "{{ $token }}";
        const botUsername = "{{ $botUsername ?? 'spi_elms_auth_bot' }}";
        const initialUser = @json($currentUser ?? null);
        const serverAvailableAccounts = @json($availableAccounts ?? []);

        let activeUser = initialUser || null;
        let tgUser = null;
        let isApproved = false;

        // Initialize Telegram WebApp and User State
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Check if opened inside Telegram WebApp
            if (window.Telegram && window.Telegram.WebApp) {
                const tg = window.Telegram.WebApp;
                try {
                    tg.ready();
                    tg.expand();
                    tg.setHeaderColor('#0b0b0d');
                    tg.setBackgroundColor('#0b0b0d');
                } catch (e) {}

                // Extract User Data if opened inside Telegram
                if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                    tgUser = tg.initDataUnsafe.user;
                    activeUser = {
                        id: tgUser.id,
                        name: [tgUser.first_name, tgUser.last_name].filter(Boolean).join(' ') || tgUser.username || 'Telegram User',
                        username: tgUser.username,
                        avatar: tgUser.photo_url || '/uploads/avatars/avatar_1_1785245469.jpg',
                        role: 'STUDENT',
                        isTelegram: true,
                    };
                    saveAccountToHistory(activeUser);
                }
            }

            // 2. Check localStorage if user previously selected another account
            const savedSelected = localStorage.getItem('spi_selected_account');
            if (savedSelected) {
                try {
                    activeUser = JSON.parse(savedSelected);
                } catch (e) {}
            }

            // Fallback default: If still null, pick first server available account
            if (!activeUser && serverAvailableAccounts && serverAvailableAccounts.length > 0) {
                activeUser = serverAvailableAccounts[0];
            }

            // 3. Update the Top Right Pill UI (Image 2)
            updateUserPillUI(activeUser);
            renderAccountsList();

            // 4. Start polling status in background
            if (qrToken) {
                startPollingStatus();
            }
        });

        function updateUserPillUI(user) {
            const avatarEl = document.getElementById('userAvatar');
            const avatarUrl = user?.avatar || user?.photo_url || '/uploads/avatars/avatar_1_1785245469.jpg';

            if (avatarEl) {
                avatarEl.src = avatarUrl;
            }
        }

        function saveAccountToHistory(user) {
            if (!user || !user.id) return;
            localStorage.setItem('spi_selected_account', JSON.stringify(user));

            try {
                let list = JSON.parse(localStorage.getItem('spi_account_history') || '[]');
                list = list.filter(item => String(item.id) !== String(user.id));
                list.unshift(user);
                if (list.length > 8) list = list.slice(0, 8);
                localStorage.setItem('spi_account_history', JSON.stringify(list));
            } catch (e) {}
        }

        function getAllAccounts() {
            const map = new Map();
            
            // Server accounts
            if (Array.isArray(serverAvailableAccounts)) {
                serverAvailableAccounts.forEach(acc => {
                    if (acc && acc.id) map.set(String(acc.id), acc);
                });
            }

            // LocalStorage history
            try {
                const localList = JSON.parse(localStorage.getItem('spi_account_history') || '[]');
                if (Array.isArray(localList)) {
                    localList.forEach(acc => {
                        if (acc && acc.id) map.set(String(acc.id), acc);
                    });
                }
            } catch (e) {}

            // Current active user
            if (activeUser && activeUser.id) {
                map.set(String(activeUser.id), activeUser);
            }

            return Array.from(map.values());
        }

        function renderAccountsList() {
            const container = document.getElementById('accountsListContainer');
            if (!container) return;

            const accounts = getAllAccounts();
            container.innerHTML = '';

            accounts.forEach(acc => {
                const isSelected = activeUser && String(activeUser.id) === String(acc.id);
                const avatarSrc = acc.avatar || '/uploads/avatars/avatar_1_1785245469.jpg';
                const roleLabel = (acc.role || 'Active').toUpperCase();
                const identifierText = acc.student_code || acc.phone || acc.email || (acc.username ? '@' + acc.username : '');

                const card = document.createElement('div');
                card.className = `p-3 rounded-2xl flex items-center justify-between gap-3 cursor-pointer transition-all active:scale-[0.98] border ${
                    isSelected 
                        ? 'bg-zinc-900 border-emerald-500/60 shadow-lg shadow-emerald-500/10 ring-1 ring-emerald-500/40' 
                        : 'bg-zinc-950/80 hover:bg-zinc-900 border-zinc-800/80'
                }`;

                card.innerHTML = `
                    <div class="flex items-center gap-3 min-w-0">
                        <img 
                            src="${avatarSrc}" 
                            class="w-10 h-10 rounded-full object-cover bg-zinc-800 ring-2 shrink-0 ${isSelected ? 'ring-emerald-500' : 'ring-zinc-700/50'}"
                            onerror="this.src='/uploads/avatars/avatar_1_1785245469.jpg'"
                        />
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h4 class="text-xs sm:text-sm font-bold text-white truncate">${acc.name}</h4>
                                <span class="text-[9px] font-semibold px-1.5 py-0.2 rounded-full bg-zinc-800 text-zinc-300 border border-zinc-700">
                                    ${roleLabel}
                                </span>
                            </div>
                            <p class="text-[11px] text-zinc-400 truncate mt-0.5">${identifierText}</p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        ${isSelected 
                            ? `<span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 bg-emerald-500/15 border border-emerald-500/30 px-2 py-1 rounded-full">
                                 ✓ ជ្រើសរើស
                               </span>`
                            : `<span class="text-[11px] font-medium text-zinc-400 hover:text-white px-2 py-1 rounded-full bg-zinc-800/60">
                                 ចុចជ្រើស
                               </span>`
                        }
                    </div>
                `;

                card.onclick = () => {
                    activeUser = acc;
                    updateUserPillUI(activeUser);
                    saveAccountToHistory(activeUser);
                    renderAccountsList();
                    showFeedback(`✅ បានជ្រើសរើសគណនី៖ ${acc.name}`, 'success');
                    setTimeout(() => closeAccountSelectorModal(), 300);
                };

                container.appendChild(card);
            });
        }

        function openAccountSelectorModal() {
            const modal = document.getElementById('accountModal');
            if (modal) modal.classList.remove('hidden');
            renderAccountsList();
        }

        function closeAccountSelectorModal() {
            const modal = document.getElementById('accountModal');
            if (modal) modal.classList.add('hidden');
        }

        async function lookupOrSwitchAccount() {
            const input = document.getElementById('customIdentifierInput');
            const btn = document.getElementById('verifySwitchBtn');
            const val = input?.value.trim();

            if (!val) {
                showFeedback('សូមបញ្ចូលលេខទូរស័ព្ទ ឬ Student Code', 'error');
                return;
            }

            btn.disabled = true;
            btn.textContent = '...';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('/auth/telegram/confirm-sheet/lookup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ identifier: val }),
                });

                const data = await res.json();
                btn.disabled = false;
                btn.textContent = 'ស្វែងរក';

                if (res.ok && data.success && data.user) {
                    activeUser = data.user;
                    updateUserPillUI(activeUser);
                    saveAccountToHistory(activeUser);
                    renderAccountsList();
                    showFeedback(`✅ ស្គាល់គណនី៖ ${data.user.name}`, 'success');
                    if (input) input.value = '';
                    setTimeout(() => closeAccountSelectorModal(), 600);
                } else {
                    showFeedback(data.message || 'រកមិនឃើញគណនីនេះទេ', 'error');
                }
            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'ស្វែងរក';
                showFeedback('មានបញ្ហាក្នុងការតភ្ជាប់', 'error');
            }
        }

        function showFeedback(msg, type) {
            const el = document.getElementById('accountLookupFeedback');
            if (!el) return;
            el.textContent = msg;
            el.classList.remove('hidden', 'text-emerald-400', 'text-rose-400');
            el.classList.add(type === 'success' ? 'text-emerald-400' : 'text-rose-400');
        }

        function handleOpenTelegramApp(e) {
            if (e) e.preventDefault();
            const deepLink = `tg://resolve?domain=${botUsername}&start=login_${qrToken}`;
            const webLink = `https://t.me/${botUsername}?start=login_${qrToken}`;

            window.location.href = deepLink;
            setTimeout(() => {
                window.location.href = webLink;
            }, 600);
        }

        function handleClose() {
            if (window.Telegram && window.Telegram.WebApp) {
                try {
                    window.Telegram.WebApp.close();
                    return;
                } catch (e) {}
            }
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.close();
            }
        }

        function markSuccessAndClose() {
            isApproved = true;
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginBtnSpinner = document.getElementById('loginBtnSpinner');
            if (loginBtnSpinner) loginBtnSpinner.classList.add('hidden');
            if (loginBtnText) loginBtnText.textContent = '✓ Logged In!';
            if (loginBtn) {
                loginBtn.classList.remove('bg-[#34c759]');
                loginBtn.classList.add('bg-emerald-400');
                loginBtn.disabled = true;
            }
            showStatus('✅ Login ជោគជ័យ! សូមក្រឡេកមើលអេក្រង់កុំព្យូទ័ររបស់អ្នក។', 'success');

            setTimeout(() => {
                handleClose();
            }, 1800);
        }

        async function submitLoginApproval() {
            if (isApproved) return;

            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginBtnSpinner = document.getElementById('loginBtnSpinner');

            loginBtn.disabled = true;
            loginBtnSpinner.classList.remove('hidden');
            loginBtnText.textContent = 'Verifying...';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const initData = window.Telegram?.WebApp?.initData || '';

                const payload = {
                    token: qrToken,
                    init_data: initData,
                    user: tgUser || (activeUser?.isTelegram ? activeUser : null),
                    user_id: activeUser?.id || null,
                };

                const response = await fetch('/auth/telegram/confirm-sheet/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (data.user) {
                        saveAccountToHistory(data.user);
                    }
                    markSuccessAndClose();
                } else if (data.open_telegram) {
                    // Prompt Telegram app launch
                    loginBtnSpinner.classList.add('hidden');
                    loginBtnText.textContent = '🚀 Opening Telegram...';
                    showStatus('🚀 កំពុងបើក Telegram App... សូមចុច START ដើម្បី Login', 'success');

                    const tgDeepLink = data.tg_deep_link || (`tg://resolve?domain=${botUsername}&start=login_${qrToken}`);
                    const tgWebLink = data.telegram_url || (`https://t.me/${botUsername}?start=login_${qrToken}`);

                    window.location.href = tgDeepLink;
                    setTimeout(() => {
                        window.location.href = tgWebLink;
                    }, 600);

                    setTimeout(() => {
                        loginBtn.disabled = false;
                        loginBtnText.textContent = 'Log In';
                    }, 3000);
                } else {
                    loginBtn.disabled = false;
                    loginBtnSpinner.classList.add('hidden');
                    loginBtnText.textContent = 'Log In';
                    showStatus(data.message || 'ការផ្ទៀងផ្ទាត់មិនទាន់ជោគជ័យ', 'error');

                    if (!activeUser) {
                        openAccountSelectorModal();
                    }
                }
            } catch (err) {
                loginBtnSpinner.classList.add('hidden');
                loginBtnText.textContent = 'Log In';
                loginBtn.disabled = false;
                window.location.href = `tg://resolve?domain=${botUsername}&start=login_${qrToken}`;
                setTimeout(() => {
                    window.location.href = `https://t.me/${botUsername}?start=login_${qrToken}`;
                }, 600);
            }
        }

        function startPollingStatus() {
            const timer = setInterval(async () => {
                if (isApproved) {
                    clearInterval(timer);
                    return;
                }
                try {
                    const res = await fetch(`/auth/telegram/qr-status?token=${encodeURIComponent(qrToken)}`);
                    const data = await res.json();
                    if (data.status === 'approved') {
                        clearInterval(timer);
                        markSuccessAndClose();
                    }
                } catch (e) {}
            }, 1500);
        }

        function showStatus(msg, type) {
            const banner = document.getElementById('statusBanner');
            if (!banner) return;
            banner.textContent = msg;
            banner.classList.remove('hidden', 'bg-emerald-500/20', 'text-emerald-300', 'border-emerald-500/30', 'bg-rose-500/20', 'text-rose-300', 'border-rose-500/30');
            
            if (type === 'success') {
                banner.classList.add('bg-emerald-500/20', 'text-emerald-300', 'border', 'border-emerald-500/30');
            } else {
                banner.classList.add('bg-rose-500/20', 'text-rose-300', 'border', 'border-rose-500/30');
            }
        }
    </script>
</body>
</html>
