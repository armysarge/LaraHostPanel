<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', sidebarOpen: true }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LaraHostPanel')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100 font-sans antialiased">
    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-30 flex flex-col border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 transition-all duration-300"
            :class="sidebarOpen ? 'w-64' : 'w-16'"
        >
            {{-- Logo --}}
            <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-4 dark:border-gray-800">
                <img src="{{ asset('assets/images/icon.png') }}" alt="LaraHostPanel" class="h-9 w-9 shrink-0 rounded-lg object-contain">
                <span x-show="sidebarOpen" x-transition class="text-lg font-semibold truncate">
                    LaraHostPanel
                </span>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto p-3 space-y-1">
                @include('partials.sidebar-item', ['route' => 'dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'])
                @include('partials.sidebar-item', ['route' => 'projects.index', 'icon' => 'projects', 'label' => 'Projects'])
                @include('partials.sidebar-item', ['route' => 'dashboard', 'icon' => 'deployments', 'label' => 'Deployments'])
                @include('partials.sidebar-item', ['route' => 'dashboard', 'icon' => 'credentials', 'label' => 'Credentials'])
                @include('partials.sidebar-item', ['route' => 'dashboard', 'icon' => 'settings', 'label' => 'Settings'])
            </nav>

            {{-- Collapse toggle --}}
            <div class="border-t border-gray-200 p-3 dark:border-gray-800">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="flex w-full items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
                >
                    <svg x-show="sidebarOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                    <svg x-show="!sidebarOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </aside>

        {{-- Main content area --}}
        <div class="flex flex-1 flex-col transition-all duration-300" :class="sidebarOpen ? 'ml-64' : 'ml-16'">

            {{-- Top bar --}}
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white/80 px-6 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
                <h1 class="text-lg font-semibold">@yield('heading', 'Dashboard')</h1>

                <div class="flex items-center gap-3">
                    {{-- Dark mode toggle --}}
                    <button
                        @click="darkMode = !darkMode"
                        class="relative flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
                        title="Toggle theme"
                    >
                        <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    {{-- User menu --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300">
                                {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                            </div>
                            <span class="hidden text-sm font-medium sm:block">{{ Auth::user()->name ?? 'User' }}</span>
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div
                            x-show="open"
                            @click.outside="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 origin-top-right rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                        >
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 p-6">
                @yield('content')
            </main>

            {{-- Bottom bar --}}
            <footer class="flex h-10 items-center justify-between border-t border-gray-200 bg-white px-6 text-xs text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                <span>&copy; {{ date('Y') }} LaraHostPanel</span>
                <span>v1.0.0</span>
            </footer>
        </div>
    </div>
</body>
</html>
