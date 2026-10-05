<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no" class="dark notranslate" style="color-scheme: dark; background-color: #0b132b;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="google" content="notranslate">
    <meta name="googlebot" content="notranslate">
    <meta name="theme-color" content="#0b132b">

    <!-- Anti-FOUC & Instant Theme Injection (Runs Synchronously Before First Paint) -->
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (storedTheme === 'dark' || (!storedTheme && prefersDark) || storedTheme === null) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.style.colorScheme = 'dark';
                    document.documentElement.style.backgroundColor = '#0b132b';
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.style.colorScheme = 'light';
                    document.documentElement.style.backgroundColor = '#f8fafc';
                }
                var storedLang = localStorage.getItem('elms_lang');
                if (storedLang === 'en' || storedLang === 'km') {
                    document.documentElement.lang = storedLang;
                }
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        /* Instant baseline styling to eliminate White Flash / FOUC on reload */
        html {
            background-color: #0b132b;
            color-scheme: dark;
            -webkit-text-size-adjust: 100%;
            text-rendering: optimizeLegibility;
        }

        html.dark {
            background-color: #0b132b !important;
            color: #f8fafc !important;
        }

        html:not(.dark) {
            background-color: #f8fafc !important;
            color: #0f172a !important;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            background-color: #0b132b;
        }

        html:not(.dark) body {
            background-color: #f8fafc;
        }

        [v-cloak] {
            display: none !important;
        }
    </style>

    <!-- DNS Prefetch & Preconnect for High-Speed CDN & Media Loading -->
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="dns-prefetch" href="https://images.unsplash.com">
    <link rel="dns-prefetch" href="https://ui-avatars.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Koh+Santepheap:wght@400;700;900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

    <!-- Favicon Links for Google Search, Mobile, and Desktop Browsers -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="144x144" href="/favicon-144x144.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512x512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Primary Meta Tags & SEO Snippet -->
    <title inertia>E-LMS | Smart Learning Management System — Saint Paul Institute</title>
    <meta name="title" content="SPI AI-ELMS | Smart Learning Management System — Saint Paul Institute">
    <meta name="description"
        content="SPI AI-ELMS — Intelligent Next-Generation Learning Management System for Students & Faculty at Saint Paul Institute. Access smart courses, live schedules, and academic excellence.">
    <meta name="keywords"
        content="SPI AI-ELMS, Saint Paul Institute, ELMS, SPI, Smart Learning Platform, Higher Education, spilms, e-learning">
    <meta name="author" content="Saint Paul Institute">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook / Telegram Previews -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://spilms.tech/">
    <meta property="og:title" content="SPI AI-ELMS | Smart Learning Management System — Saint Paul Institute">
    <meta property="og:description"
        content="SPI AI-ELMS — Intelligent Next-Generation Learning Management System for Students & Faculty at Saint Paul Institute. Access smart courses, live schedules, and academic excellence.">
    <meta property="og:image" content="https://spilms.tech/images/og-cover.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="SPI AI-ELMS">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://spilms.tech/">
    <meta name="twitter:title" content="SPI AI-ELMS | Smart Learning Management System — Saint Paul Institute">
    <meta name="twitter:description"
        content="SPI AI-ELMS — Intelligent Next-Generation Learning Management System for Students & Faculty at Saint Paul Institute. Access smart courses, live schedules, and academic excellence.">
    <meta name="twitter:image" content="https://spilms.tech/images/og-cover.png">

    <!-- Structured Data (Schema.org) for Google Search & Knowledge Graph -->
    <script type="application/ld+json">
    @verbatim
        {
          "@context": "https://schema.org",
          "@type": "EducationalOrganization",
          "name": "SPI AI-ELMS",
          "alternateName": "Saint Paul Institute E-Learning Management System",
          "url": "https://spilms.tech",
          "logo": "https://spilms.tech/images/logo.png",
          "image": "https://spilms.tech/images/og-cover.png",
          "description": "Intelligent AI-Powered Next-Generation Learning Platform of Saint Paul Institute",
          "sameAs": [
            "https://spi.edu.kh"
          ]
        }
    @endverbatim
    </script>

    @routes
    <!-- Google Identity Services SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>



    <script>
        // Safe Storage Helper (avoids SecurityError in iOS Private Browsing / Telegram WebViews)
        function safeGetSession(key) {
            try { return sessionStorage.getItem(key); } catch (e) { return null; }
        }
        function safeSetSession(key, val) {
            try { sessionStorage.setItem(key, val); } catch (e) { }
        }

        // Global Error & Chunk Load Recovery for Mobile WebViews
        function handleChunkError(err) {
            var msg = String(err || '');
            if (
                msg.indexOf('dynamically imported module') !== -1 ||
                msg.indexOf('Importing a module script failed') !== -1 ||
                msg.indexOf('Failed to fetch') !== -1 ||
                msg.indexOf('Loading chunk') !== -1 ||
                msg.indexOf('Load failed') !== -1
            ) {
                if (!safeGetSession('chunk_reload_done')) {
                    safeSetSession('chunk_reload_done', '1');
                    window.location.reload();
                }
            }
        }

        window.addEventListener('unhandledrejection', function (e) {
            handleChunkError(e.reason);
        });

        window.addEventListener('error', function (e) {
            handleChunkError(e.message || (e.error && e.error.message));
        });
    </script>

    @vite('resources/js/app.ts')
    @inertiaHead
</head>

<body class="font-sans antialiased bg-[#0b132b] text-slate-100 min-h-screen notranslate" translate="no"
    style="background-color: #0b132b; color: #f8fafc;">
    
    <!-- Instant Initial Page Preloader (Exact match to requested 4-dot loader) -->
    <div id="initial-page-preloader"
        style="position: fixed; inset: 0; z-index: 999999; display: flex; flex-direction: column; align-items: center; justify-content: center; background-color: #0b132b; transition: opacity 0.45s ease-out; pointer-events: none;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 16px;">
            <span class="init-dot init-dot-1" style="width: 18px; height: 18px; border-radius: 50%; background: #475569; animation: initDotTravel 1.8s infinite ease-in-out; animation-delay: 0s;"></span>
            <span class="init-dot init-dot-2" style="width: 18px; height: 18px; border-radius: 50%; background: #475569; animation: initDotTravel 1.8s infinite ease-in-out; animation-delay: 0.3s;"></span>
            <span class="init-dot init-dot-3" style="width: 18px; height: 18px; border-radius: 50%; background: #475569; animation: initDotTravel 1.8s infinite ease-in-out; animation-delay: 0.6s;"></span>
            <span class="init-dot init-dot-4" style="width: 18px; height: 18px; border-radius: 50%; background: #475569; animation: initDotTravel 1.8s infinite ease-in-out; animation-delay: 0.9s;"></span>
        </div>
        <p style="margin: 0; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; font-size: 14px; font-weight: 600; color: #cbd5e1; letter-spacing: 0.4px;">
            Please wait while loading
        </p>
    </div>
    <style>
        @keyframes initDotTravel {
            0%, 100% { background-color: #475569; transform: scale(0.9); opacity: 0.65; box-shadow: none; }
            20%, 35% { background-color: #ea580c; transform: scale(1.22); opacity: 1; box-shadow: 0 0 16px rgba(234, 88, 12, 0.65); }
            50% { background-color: #475569; transform: scale(0.95); opacity: 0.7; box-shadow: none; }
        }
    </style>

    @inertia
</body>

</html>