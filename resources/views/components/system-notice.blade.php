@php
    $enabled = (bool) config('student.notice.enabled', false);
    $isStudentPage = request()->is('student')
        || request()->is('student/*')
        || request()->is('my courses*')
        || request()->is('my_courses*');
    $noticeUrl = config('student.notice.url');
    $kmText = trans('notice.system_improving', [], 'km');
    $enText = trans('notice.system_improving', [], 'en');
    $kmReport = trans('notice.report', [], 'km');
    $enReport = trans('notice.report', [], 'en');
@endphp

@if($enabled && $isStudentPage)
    <div
        id="student-system-notice"
        class="student-system-notice"
        role="status"
        aria-live="polite"
        data-km-text="{{ $kmText }}"
        data-en-text="{{ $enText }}"
        data-km-report="{{ $kmReport }}"
        data-en-report="{{ $enReport }}"
    >
        <div class="student-system-notice__inner">
            <svg
                class="student-system-notice__icon"
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
            </svg>
            <span id="student-system-notice-text" class="student-system-notice__text">{{ __('notice.system_improving') }}</span>
            @if(!empty($noticeUrl))
                <a
                    id="student-system-notice-link"
                    href="{{ $noticeUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="student-system-notice__link"
                >{{ __('notice.report') }}</a>
            @endif
        </div>
    </div>

    <style>
        .student-system-notice {
            position: sticky;
            top: 0;
            z-index: 60;
            min-height: 44px;
            width: 100%;
            box-sizing: border-box;
            background-color: #fff4d6;
            color: #6b3f00;
            border-bottom: 1px solid #f5c451;
            font-size: 14px;
            line-height: 1.6;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }

        html.dark .student-system-notice {
            background-color: #3a2a07;
            color: #fbbf24;
            border-bottom: 1px solid #7a5a12;
        }

        .student-system-notice__inner {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px 10px;
            max-width: 1280px;
            text-align: center;
        }

        .student-system-notice__icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .student-system-notice__text {
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .student-system-notice__link {
            color: inherit;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        @media (max-width: 640px) {
            .student-system-notice {
                font-size: 13px;
                line-height: 1.6;
                padding: 8px 12px;
            }
        }
    </style>

    <script>
        (function () {
            var root = document.documentElement;
            var el = document.getElementById('student-system-notice');

            function updateNoticeHeight() {
                if (!el || el.offsetParent === null) {
                    root.style.setProperty('--notice-h', '0px');
                    return;
                }
                root.style.setProperty('--notice-h', el.offsetHeight + 'px');
            }

            function applyNoticeLanguage(lang) {
                if (!el) return;
                var safeLang = (lang === 'en') ? 'en' : 'km';
                var textEl = document.getElementById('student-system-notice-text');
                var linkEl = document.getElementById('student-system-notice-link');
                if (textEl) {
                    var msg = el.getAttribute(safeLang === 'en' ? 'data-en-text' : 'data-km-text');
                    if (msg) textEl.textContent = msg;
                }
                if (linkEl) {
                    var rep = el.getAttribute(safeLang === 'en' ? 'data-en-report' : 'data-km-report');
                    if (rep) linkEl.textContent = rep;
                }
                updateNoticeHeight();
            }

            try {
                var storedLang = localStorage.getItem('elms_lang');
                if (storedLang === 'en' || storedLang === 'km') {
                    applyNoticeLanguage(storedLang);
                }
            } catch (e) {}

            updateNoticeHeight();

            if (el && typeof ResizeObserver !== 'undefined') {
                var ro = new ResizeObserver(function () {
                    updateNoticeHeight();
                });
                ro.observe(el);
            }

            window.addEventListener('resize', updateNoticeHeight);
            window.addEventListener('elms-lang-change', function (e) {
                if (e && e.detail) {
                    applyNoticeLanguage(e.detail);
                }
            });
        })();
    </script>
@endif
