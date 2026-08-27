<!DOCTYPE html>
<html lang="km" class="dark" style="color-scheme: dark; background-color: #0b132b;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
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

    <!-- Preconnect & Preload Asynchronous Font Loading (font-display: swap) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Inter:wght@300;400;500;600;700;800&family=Kantumruy+Pro:ital,wght@0,300..700;1,300..700&family=Koh+Santepheap:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link
            href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Inter:wght@300;400;500;600;700;800&family=Kantumruy+Pro:ital,wght@0,300..700;1,300..700&family=Koh+Santepheap:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap"
            rel="stylesheet">
    </noscript>

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

    <!-- PWA Service Worker Registration & Offline Network Handler -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(function (reg) {
                        console.log('SPI AI-ELMS Service Worker Registered successfully with scope:', reg.scope);
                    })
                    .catch(function (err) {
                        console.warn('Service Worker Registration notice:', err);
                    });
            });
        }

        // Live Network Status Observer
        function updateOnlineStatus() {
            var statusEl = document.getElementById('network-status');
            if (statusEl) {
                if (navigator.onLine) {
                    statusEl.innerHTML = '● Online';
                    statusEl.className = 'px-3 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                } else {
                    statusEl.innerHTML = '● Offline';
                    statusEl.className = 'px-3 py-1 text-xs font-semibold rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20';
                }
            }
        }

        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        document.addEventListener('DOMContentLoaded', updateOnlineStatus);
    </script>

    <!-- Primary Meta Tags & SEO Snippet -->
    <title inertia>SPI AI-ELMS | ប្រព័ន្ធគ្រប់គ្រងការសិក្សា Saint Paul Institute</title>
    <meta name="title" content="SPI AI-ELMS | ប្រព័ន្ធគ្រប់គ្រងការសិក្សា Saint Paul Institute">
    <meta name="description"
        content="ប្រព័ន្ធគ្រប់គ្រងការសិក្សាឆ្លាតវៃ SPI AI-ELMS សម្រាប់និស្សិត និងសាស្ត្រាចារ្យ នៃវិទ្យាស្ថានសន្តប៉ូល។ ចូលប្រើប្រាស់ដើម្បីពិនិត្យកាលវិភាគ ពិន្ទុ និងមេរៀន។">
    <meta name="keywords"
        content="SPI AI-ELMS, Saint Paul Institute, ELMS, SPI, វិទ្យាស្ថានសន្តប៉ូល, ប្រព័ន្ធគ្រប់គ្រងការសិក្សា, spilms, e-learning">
    <meta name="author" content="Saint Paul Institute">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook / Telegram Previews -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://spilms.tech/">
    <meta property="og:title" content="SPI AI-ELMS | ប្រព័ន្ធគ្រប់គ្រងការសិក្សា Saint Paul Institute">
    <meta property="og:description"
        content="ចូលប្រើប្រាស់ប្រព័ន្ធគ្រប់គ្រងការសិក្សា SPI AI-ELMS ដោយសុវត្ថិភាព និងរហ័ស។">
    <meta property="og:image" content="https://spilms.tech/images/og-cover.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="SPI AI-ELMS">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://spilms.tech/">
    <meta name="twitter:title" content="SPI AI-ELMS | ប្រព័ន្ធគ្រប់គ្រងការសិក្សា Saint Paul Institute">
    <meta name="twitter:description"
        content="ចូលប្រើប្រាស់ប្រព័ន្ធគ្រប់គ្រងការសិក្សា SPI AI-ELMS ដោយសុវត្ថិភាព និងរហ័ស។">
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
          "description": "ប្រព័ន្ធគ្រប់គ្រងការសិក្សាឆ្លាតវៃនៃវិទ្យាស្ថានសន្តប៉ូល (Saint Paul Institute)",
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

<body class="font-sans antialiased bg-[#0b132b] text-slate-100 min-h-screen"
    style="background-color: #0b132b; color: #f8fafc;">
    @inertia
</body>

</html>