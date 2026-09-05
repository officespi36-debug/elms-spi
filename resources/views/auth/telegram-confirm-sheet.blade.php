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
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- User Profile Avatar with Account Switcher Badge -->
        <div class="relative cursor-pointer group">
            <div id="userAvatarContainer" class="w-9 h-9 rounded-full bg-gradient-to-tr from-sky-500 to-indigo-600 p-[1.5px] shadow-md">
                <img
                    id="userAvatar"
                    src="/images/logo.png"
                    alt="User"
                    class="w-full h-full rounded-full object-cover bg-zinc-900"
                />
            </div>
            <!-- Switcher Icon <> -->
            <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-zinc-800 border border-zinc-600 flex items-center justify-center text-[8px] text-zinc-300 font-bold shadow-xs">
                <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 18 22 12 16 6"></polyline>
                    <polyline points="8 6 2 12 8 18"></polyline>
                </svg>
            </div>
        </div>
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

    <script>
        const qrToken = "{{ $token }}";
        let tgUser = null;
        let isApproved = false;

        // Initialize Telegram WebApp
        document.addEventListener('DOMContentLoaded', () => {
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
                    if (tgUser.photo_url) {
                        const avatarEl = document.getElementById('userAvatar');
                        if (avatarEl) avatarEl.src = tgUser.photo_url;
                    }
                }
            }

            // Start polling status in background so if approved via Telegram, this sheet completes
            if (qrToken) {
                startPollingStatus();
            }
        });

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

                const response = await fetch('/auth/telegram/confirm-sheet/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        token: qrToken,
                        init_data: initData,
                        user: tgUser,
                    }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    markSuccessAndClose();
                } else if (data.open_telegram) {
                    // Outside Telegram: Seamlessly launch Telegram App
                    loginBtnSpinner.classList.add('hidden');
                    loginBtnText.textContent = '🚀 Opening Telegram...';
                    showStatus('🚀 កំពុងបើក Telegram App... សូមចុច START ដើម្បី Login', 'success');

                    const tgDeepLink = data.tg_deep_link || ("tg://resolve?domain={{ config('services.telegram.bot_username') ?: 'spi_elms_auth_bot' }}&start=login_" + qrToken);
                    const tgWebLink = data.telegram_url || ("https://t.me/{{ config('services.telegram.bot_username') ?: 'spi_elms_auth_bot' }}?start=login_" + qrToken);

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
                }
            } catch (err) {
                // Network fallback: launch Telegram App directly
                loginBtnSpinner.classList.add('hidden');
                loginBtnText.textContent = 'Log In';
                loginBtn.disabled = false;
                window.location.href = "tg://resolve?domain={{ config('services.telegram.bot_username') ?: 'spi_elms_auth_bot' }}&start=login_" + qrToken;
                setTimeout(() => {
                    window.location.href = "https://t.me/{{ config('services.telegram.bot_username') ?: 'spi_elms_auth_bot' }}?start=login_" + qrToken;
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
