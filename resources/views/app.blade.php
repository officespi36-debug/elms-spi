<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no" class="notranslate" style="color-scheme: light; background-color: #f8fafc;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="google" content="notranslate">
    <meta name="googlebot" content="notranslate">
    <meta name="theme-color" content="#f8fafc">

    <!-- Anti-FOUC & Instant Theme Injection (Runs Synchronously Before First Paint) -->
    <script>
        (function () {
            try {
                if (localStorage.getItem('elms_theme_v2') !== '1') {
                    localStorage.setItem('elms_theme_v2', '1');
                    localStorage.setItem('theme', 'light');
                }

                var storedTheme = localStorage.getItem('theme');
                if (storedTheme === 'dark') {
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
                document.documentElement.classList.remove('dark');
                document.documentElement.style.colorScheme = 'light';
                document.documentElement.style.backgroundColor = '#f8fafc';
            }

            try {
                if ('caches' in window) {
                    caches.keys().then(function(keys) {
                        for (var i = 0; i < keys.length; i++) {
                            if (keys[i].indexOf('spi-elms-v') !== -1) {
                                caches.delete(keys[i]);
                            }
                        }
                    });
                }
            } catch (e) {}
        })();
    </script>
    <style>
        /* Instant baseline styling to eliminate White Flash / FOUC / Layout Shift on reload */
        html {
            background-color: #f8fafc;
            color-scheme: light;
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
        }

        #initial-page-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            pointer-events: none;
            user-select: none;
            overflow: hidden;
            contain: strict;
            will-change: opacity;
            transform: translate3d(0, 0, 0);
            backface-visibility: hidden;
            opacity: 1;
            transition: opacity 0.25s cubic-bezier(0.22, 1, 0.36, 1);
        }

        html.dark #initial-page-preloader {
            background-color: #0b132b;
            color: #f8fafc;
        }

        html:not(.dark) #initial-page-preloader {
            background-color: #f8fafc;
            color: #0f172a;
        }

        .init-ring-wrap {
            width: 68px;
            height: 68px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transform-origin: 50% 50% !important;
            -webkit-transform-origin: 50% 50% !important;
            will-change: transform;
            transform: translate3d(0, 0, 0);
            backface-visibility: hidden;
            filter: drop-shadow(0 2px 8px rgba(0, 153, 255, 0.35));
            -webkit-animation: initRingSpin 1.1s linear infinite !important;
            animation: initRingSpin 1.1s linear infinite !important;
        }

        .init-ring-svg {
            display: block;
            width: 100%;
            height: 100%;
            transform-origin: 50% 50% !important;
            -webkit-transform-origin: 50% 50% !important;
        }

        @-webkit-keyframes initRingSpin {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }

        @keyframes initRingSpin {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }

        #initial-preloader-text {
            margin: 0;
            height: 26px;
            line-height: 26px;
            font-family: 'Plus Jakarta Sans', 'Kantumruy Pro', system-ui, -apple-system, sans-serif;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.3px;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
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

<body class="font-sans antialiased min-h-screen notranslate" translate="no">
    <!-- Instant Initial Page Preloader (GPU-Composited 12-Dot Spinning Blue Ring + Text) -->
    <div id="initial-page-preloader" role="status" aria-live="polite">
        <div class="init-ring-wrap" aria-hidden="true">
            <svg class="init-ring-svg" width="68" height="68" viewBox="0 0 64 64" fill="none">
                <circle cx="32" cy="9" r="5.2" fill="#0099ff" fill-opacity="1" transform="rotate(0 32 32)" />
                <circle cx="32" cy="9" r="4.8" fill="#0099ff" fill-opacity="0.96" transform="rotate(30 32 32)" />
                <circle cx="32" cy="9" r="4.3" fill="#0099ff" fill-opacity="0.92" transform="rotate(60 32 32)" />
                <circle cx="32" cy="9" r="3.7" fill="#0099ff" fill-opacity="0.88" transform="rotate(90 32 32)" />
                <circle cx="32" cy="9" r="3.0" fill="#0099ff" fill-opacity="0.84" transform="rotate(120 32 32)" />
                <circle cx="32" cy="9" r="2.0" fill="#0099ff" fill-opacity="0.78" transform="rotate(150 32 32)" />
                <circle cx="32" cy="9" r="1.1" fill="#0099ff" fill-opacity="0.72" transform="rotate(180 32 32)" />
                <circle cx="32" cy="9" r="1.8" fill="#0099ff" fill-opacity="0.78" transform="rotate(210 32 32)" />
                <circle cx="32" cy="9" r="2.7" fill="#0099ff" fill-opacity="0.84" transform="rotate(240 32 32)" />
                <circle cx="32" cy="9" r="3.6" fill="#0099ff" fill-opacity="0.88" transform="rotate(270 32 32)" />
                <circle cx="32" cy="9" r="4.3" fill="#0099ff" fill-opacity="0.94" transform="rotate(300 32 32)" />
                <circle cx="32" cy="9" r="4.8" fill="#0099ff" fill-opacity="0.98" transform="rotate(330 32 32)" />
            </svg>
        </div>
        <p id="initial-preloader-text">
            សូមរង់ចាំ កំពុងដំណើរការ...
        </p>
    </div>
    <script>
        (function() {
            try {
                var txt = document.getElementById('initial-preloader-text');
                if (txt) {
                    var lang = localStorage.getItem('elms_lang') || document.documentElement.lang || 'km';
                    txt.textContent = (lang === 'en') ? 'Please wait while loading...' : 'សូមរង់ចាំ កំពុងដំណើរការ...';
                }
                var ring = document.querySelector('.init-ring-wrap');
                if (ring) {
                    var start = null;
                    function spinStep(ts) {
                        if (!document.getElementById('initial-page-preloader')) return;
                        if (!start) start = ts;
                        var deg = Math.floor(((ts - start) / 1100) * 360) % 360;
                        ring.style.transform = 'rotate(' + deg + 'deg)';
                        ring.style.webkitTransform = 'rotate(' + deg + 'deg)';
                        requestAnimationFrame(spinStep);
                    }
                    requestAnimationFrame(spinStep);
                }
            } catch(e) {}
        })();
    </script>

    @inertia
</body>

</html>