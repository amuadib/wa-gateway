<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name', 'WA-Gateway') }}</title>
    <script>
        (() => {
            const stored = localStorage.getItem('wa-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans bg-slate-50 dark:bg-slate-950 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-600/20 mb-4">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                    <path d="m13 8-2 4h3l-2 4"/>
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white tracking-tight">{{ config('app.name', 'WA-Gateway') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Masuk untuk mengelola pesan dan perangkat WhatsApp</p>
        </div>

        <div class="card p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 px-3.5 py-2.5 text-xs font-medium text-rose-700 dark:text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="label-field">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                           placeholder="nama@domain.com" class="input-field">
                </div>

                <div>
                    <label for="password" class="label-field">Kata Sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           placeholder="••••••••" class="input-field">
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-emerald-600 focus:ring-emerald-500/30">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary w-full py-2.5">
                    Masuk
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400 dark:text-slate-500">
            Unified Laravel + Livewire + Baileys v7
        </p>
    </div>
</body>
</html>
