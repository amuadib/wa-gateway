<div wire:poll.20s class="rounded-lg border px-3 py-2.5 text-xs transition {{ $health['online'] ? 'border-emerald-200/80 bg-emerald-50/60 dark:border-emerald-500/20 dark:bg-emerald-500/10' : 'border-rose-200/80 bg-rose-50/60 dark:border-rose-500/20 dark:bg-rose-500/10' }}">
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="relative flex h-2 w-2">
                @if($health['online'])
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                @else
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                @endif
            </span>
            <span class="font-medium {{ $health['online'] ? 'text-emerald-800 dark:text-emerald-300' : 'text-rose-800 dark:text-rose-300' }}">
                Baileys {{ $health['online'] ? 'Online' : 'Offline' }}
            </span>
        </div>
        @if($health['online'])
            <span class="tabular-nums text-[11px] font-medium text-emerald-700/80 dark:text-emerald-400/90">
                {{ $health['connectedSessions'] }} aktif
            </span>
        @endif
    </div>
    @if($queued > 0)
        <div class="mt-1.5 flex items-center justify-between border-t border-slate-200/60 dark:border-slate-700/60 pt-1.5 text-[11px] text-slate-600 dark:text-slate-400">
            <span>Antrian pesan</span>
            <span class="tabular-nums font-semibold text-amber-700 dark:text-amber-400">{{ number_format($queued) }}</span>
        </div>
    @endif
</div>
