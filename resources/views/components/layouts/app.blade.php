<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'WA-Gateway') }}</title>
    <script>
        (() => {
            const stored = localStorage.getItem('wa-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            window.toggleWaTheme = () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('wa-theme', isDark ? 'dark' : 'light');
                window.dispatchEvent(new CustomEvent('wa-theme-changed', { detail: { dark: isDark } }));
                return isDark;
            };
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans" x-data="{ sidebarOpen: false }">

    {{-- Toast Notification Stack (Alpine.js) --}}
    <div
        x-data="{
            toasts: [],
            add(detail) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, type: detail.type || 'success', message: detail.message || '' });
                setTimeout(() => this.remove(id), 4500);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        @notify.window="add($event.detail)"
        class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-full max-w-sm pointer-events-none px-4 sm:px-0"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white dark:bg-slate-900 p-3.5 shadow-lg shadow-slate-900/5 dark:shadow-black/40"
                :class="toast.type === 'error' ? 'border-rose-200 dark:border-rose-500/30' : 'border-emerald-200 dark:border-emerald-500/30'"
            >
                <div class="mt-0.5 shrink-0">
                    <template x-if="toast.type === 'error'">
                        <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </template>
                    <template x-if="toast.type !== 'error'">
                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </template>
                </div>
                <p class="flex-1 text-xs font-medium text-slate-700 dark:text-slate-200 leading-relaxed" x-text="toast.message"></p>
                <button @click="remove(toast.id)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Mobile Backdrop --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-slate-900/40 dark:bg-black/60 backdrop-blur-[1px] lg:hidden"
    ></div>

    <div class="min-h-full flex">
        {{-- Left Sidebar --}}
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-white dark:bg-slate-900 border-r border-slate-200/80 dark:border-slate-800 flex flex-col transition-transform duration-200 ease-out lg:translate-x-0 lg:static lg:shrink-0"
        >
            {{-- Brand --}}
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                    <div class="h-8 w-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-sm shadow-emerald-600/20">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                            <path d="m13 8-2 4h3l-2 4"/>
                        </svg>
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold text-slate-900 dark:text-white tracking-tight">{{ config('app.name', 'WA-Gateway') }}</div>
                        <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Baileys v7 Engine</div>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Live Engine Status Pill --}}
            <div class="px-4 pt-4">
                <livewire:engine-status-pill />
            </div>

            {{-- Navigation Menu --}}
            @php
                $queuedNavCount = \App\Models\Message::where('sent', 0)->count();
                $isAdmin = auth()->user()?->isAdmin();
            @endphp
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                <div>
                    <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Utama
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('dashboard') ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-slate-100' }}">
                            <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('messages.index') }}"
                           class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('messages.*') ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-slate-100' }}">
                            <span class="flex items-center gap-3">
                                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('messages.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                </svg>
                                <span>Pesan</span>
                            </span>
                            @if($queuedNavCount > 0)
                                <span class="tabular-nums inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-500/20 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:text-amber-300" title="Pesan dalam antrian">
                                    {{ $queuedNavCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('devices.index') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('devices.*') ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-slate-100' }}">
                            <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('devices.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                            <span>Device</span>
                        </a>
                    </div>
                </div>

                @if($isAdmin)
                    <div>
                        <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Pengaturan
                        </div>
                        <div class="space-y-0.5">
                            <a href="{{ route('tokens.index') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('tokens.*') ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-slate-100' }}">
                                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('tokens.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                </svg>
                                <span>Aplikasi & Token</span>
                            </a>

                            <a href="{{ route('groups.index') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('groups.*') ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-slate-100' }}">
                                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('groups.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                                </svg>
                                <span>Kelompok</span>
                            </a>

                            <a href="{{ route('users.index') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('users.*') ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-slate-100' }}">
                                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('users.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <span>Pengguna</span>
                            </a>
                        </div>
                    </div>
                @endif
            </nav>

            {{-- User Profile Footer --}}
            @auth
                <div class="border-t border-slate-100 dark:border-slate-800/80 p-3">
                    <div class="flex items-center gap-3 rounded-lg p-2">
                        <div class="h-8 w-8 rounded-full bg-slate-900 dark:bg-emerald-600 text-white text-xs font-semibold flex items-center justify-center shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</div>
                            <div class="truncate text-[11px] text-slate-400 dark:text-slate-500">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Operator' }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Keluar" class="p-1.5 rounded-lg text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 hover:text-rose-600 dark:hover:text-rose-400 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </aside>

        {{-- Main Column --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-20 h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <h1 class="text-base font-semibold text-slate-900 dark:text-white truncate">{{ $title ?? 'Dashboard' }}</h1>
                </div>

                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500 tabular-nums">
                        {{ now()->translatedFormat('D, d M Y') }}
                    </span>

                    <div class="hidden sm:block h-4 w-px bg-slate-200 dark:bg-slate-800"></div>

                    {{-- Light / Dark Theme Switch --}}
                    <button
                        type="button"
                        x-data="{ darkMode: document.documentElement.classList.contains('dark') }"
                        @wa-theme-changed.window="darkMode = $event.detail.dark"
                        onclick="window.toggleWaTheme()"
                        role="switch"
                        :aria-checked="darkMode.toString()"
                        :title="darkMode ? 'Ubah ke Mode Terang' : 'Ubah ke Mode Gelap'"
                        class="group relative inline-flex h-8 w-14 items-center rounded-full border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-0.5 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                    >
                        <span class="sr-only">Ganti tema terang atau gelap</span>
                        {{-- Static background icons --}}
                        <span class="flex w-full items-center justify-between px-1.5">
                            <svg class="h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                            </svg>
                            <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                            </svg>
                        </span>
                        {{-- Sliding thumb --}}
                        <span
                            :class="darkMode ? 'translate-x-6' : 'translate-x-0'"
                            class="pointer-events-none absolute left-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-white dark:bg-slate-950 shadow-sm ring-1 ring-slate-200/80 dark:ring-slate-700 transition-transform duration-200"
                        >
                            <svg x-show="!darkMode" class="h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                            </svg>
                            <svg x-show="darkMode" x-cloak class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                            </svg>
                        </span>
                    </button>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 max-w-7xl w-full mx-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
