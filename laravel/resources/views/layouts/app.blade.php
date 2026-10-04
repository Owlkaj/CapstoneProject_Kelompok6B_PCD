<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'TomatCare Klasifikasi Penyakit Daun Tomat')</title>
    <meta name="description" content="Aplikasi klasifikasi dan diagnosis penyakit daun tomat berbasis Deep Learning EfficientNetB0 dengan akurasi 96.60%.">

    <!-- Google Fonts: Outfit (Heading) & Plus Jakarta Sans (Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#2E7D32',
                            hover: '#1B5E20',
                            light: '#E8F5E9',
                            50: '#F1F8E9',
                            100: '#DCEDC8',
                            600: '#2E7D32',
                            700: '#1B5E20',
                        },
                        neutral: {
                            dark: '#111827',
                            muted: '#4B5563',
                            border: '#E5E7EB',
                            bg: '#F9FAFB',
                        }
                    },
                    fontFamily: {
                        heading: ['Outfit', 'sans-serif'],
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F9FAFB;
            color: #111827;
        }
        h1, h2, h3, h4, .font-heading {
            font-family: 'Outfit', sans-serif;
        }
        .shimmer-pulse {
            animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-[#F9FAFB] text-[#111827] antialiased">
    <!-- Navbar -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <a href="{{ url('/detect') }}" class="flex items-center gap-2.5 group">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-700 to-green-500 flex items-center justify-center text-white text-xl shadow-sm group-hover:scale-105 transition-transform">
                        🍃
                    </span>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-heading font-bold text-xl text-gray-900 tracking-tight">TomatCare</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">AI</span>
                        </div>
                        <p class="text-[11px] text-gray-500 hidden sm:block">Tomato Leaf Disease Classifier</p>
                    </div>
                </a>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex items-center gap-1.5">
                    <a href="{{ url('/detect') }}" 
                       class="px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->is('detect') || request()->is('/') ? 'bg-[#2E7D32] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        🌿 Deteksi Daun
                    </a>
                    <a href="{{ url('/history') }}" 
                       class="px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->is('history*') ? 'bg-[#2E7D32] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        📋 Riwayat Sesi
                    </a>
                    <a href="{{ url('/about') }}" 
                       class="px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->is('about') ? 'bg-[#2E7D32] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        ℹ️ Tentang Sistem
                    </a>
                </nav>

                <!-- Session Indicator -->
                <div class="hidden lg:flex items-center gap-2 text-xs text-gray-500 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Sesi: <span class="font-mono text-gray-700" title="{{ request()->attributes->get('tomato_session_id') }}">{{ substr(request()->attributes->get('tomato_session_id', 'Session Active'), 0, 8) }}...</span></span>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button id="mobileMenuBtn" type="button" class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Drawer -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-gray-200 bg-white px-4 pt-2 pb-4 space-y-1">
            <a href="{{ url('/detect') }}" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('detect') || request()->is('/') ? 'bg-emerald-50 text-emerald-800 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                🌿 Deteksi Daun
            </a>
            <a href="{{ url('/history') }}" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('history*') ? 'bg-emerald-50 text-emerald-800 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                📋 Riwayat Sesi
            </a>
            <a href="{{ url('/about') }}" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('about') ? 'bg-emerald-50 text-emerald-800 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                ℹ️ Tentang Sistem
            </a>
            <div class="pt-2 text-xs text-gray-500 border-t border-gray-100 px-3">
                Sesi: <span class="font-mono">{{ substr(request()->attributes->get('tomato_session_id', '-'), 0, 13) }}...</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12 py-8 text-center text-sm text-gray-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-2">
            <div class="flex items-center justify-center gap-2">
                <span class="text-base">🍃</span>
                <span class="font-heading font-bold text-gray-800">TomatCare</span>
                <span class="text-gray-400">•</span>
                <span>EfficientNetB0 (Test Acc: 96.60%)</span>
            </div>
            <p class="text-xs text-gray-400 max-w-xl mx-auto">
                Sistem ini merupakan purwarupa kecerdasan buatan untuk identifikasi gejala visual penyakit daun tomat. Selalu konfirmasi dengan penyuluh pertanian atau ahli agronomi sebelum melakukan tindakan kimiawi skala besar.
            </p>
            <p class="text-xs text-gray-400">
                &copy; {{ date('Y') }} TomatCare Capstone Project. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        });
    </script>
    @stack('scripts')
</body>
</html>
