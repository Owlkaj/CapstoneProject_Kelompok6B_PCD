<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'TomatCare — Deteksi Penyakit Daun Tomat')</title>
    <meta name="description" content="Identifikasi visual penyakit daun tomat melalui foto. Dapatkan hasil analisis dan panduan foto yang jelas.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                        },
                    },
                    fontFamily: {
                        heading: ['Outfit', 'sans-serif'],
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        h1, h2, h3, h4, .font-heading {
            font-family: 'Outfit', sans-serif;
        }

        :focus-visible {
            outline: 2px solid #16a34a;
            outline-offset: 2px;
        }

        .segmented-button {
            color: #475569;
            transition: background-color 150ms ease, color 150ms ease, box-shadow 150ms ease;
        }

        .segmented-button.is-active {
            background-color: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.08);
        }

        .skeleton {
            background-color: #e2e8f0;
            border-radius: 0.5rem;
            animation: skeleton-pulse 1.6s ease-in-out infinite;
        }

        @keyframes skeleton-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.45; }
        }
    </style>

    @stack('styles')
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-900 antialiased">
    @php
        $navItems = [
            [
                'label' => 'Deteksi',
                'url' => route('detect.index'),
                'active' => request()->routeIs('home', 'detect.*'),
            ],
            [
                'label' => 'Riwayat',
                'url' => route('history.index'),
                'active' => request()->routeIs('history.*'),
            ],
            [
                'label' => 'Tentang',
                'url' => route('about'),
                'active' => request()->routeIs('about'),
            ],
        ];
    @endphp

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('detect.index') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 4c-8.5 0-14 4.5-14 11a5 5 0 0 0 5 5c7 0 9-6.5 9-16Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 20c4-6 8-9 13-11"/>
                    </svg>
                </span>
                <span class="font-heading text-lg font-bold tracking-tight text-slate-900">
                    TomatCare
                </span>
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                @foreach ($navItems as $item)
                    <a href="{{ $item['url'] }}"
                       @if ($item['active']) aria-current="page" @endif
                       class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $item['active'] ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <button id="mobileMenuBtn"
                    type="button"
                    class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 md:hidden"
                    aria-controls="mobileMenu"
                    aria-expanded="false"
                    aria-label="Buka menu navigasi">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>

        <div id="mobileMenu" class="hidden border-t border-slate-200 bg-white md:hidden">
            <nav class="mx-auto max-w-6xl space-y-1 px-4 py-3 sm:px-6" aria-label="Navigasi seluler">
                @foreach ($navItems as $item)
                    <a href="{{ $item['url'] }}"
                       @if ($item['active']) aria-current="page" @endif
                       class="block rounded-lg px-3 py-2 text-sm font-medium {{ $item['active'] ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-12 border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-700 text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 4c-8.5 0-14 4.5-14 11a5 5 0 0 0 5 5c7 0 9-6.5 9-16Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20c4-6 8-9 13-11"/>
                        </svg>
                    </span>
                    <span class="font-heading text-sm font-bold text-slate-800">TomatCare</span>
                </div>
                <p class="text-xs text-slate-500">&copy; {{ date('Y') }} TomatCare</p>
            </div>
            <p class="mt-4 max-w-3xl text-xs leading-relaxed text-slate-500">
                Hasil analisis bersifat indikatif dan bukan pengganti pemeriksaan laboratorium atau konsultasi dengan penyuluh pertanian.
            </p>
        </div>
    </footer>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');

        mobileMenuBtn?.addEventListener('click', () => {
            const isOpen = !mobileMenu.classList.contains('hidden');
            mobileMenu.classList.toggle('hidden');
            mobileMenuBtn.setAttribute('aria-expanded', String(!isOpen));
        });
    </script>

    @stack('scripts')
</body>
</html>
