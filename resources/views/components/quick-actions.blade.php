@props(['actions'])
{{-- Big buttons for the jobs done every day: [route, label, hint, icon, tone]. --}}
@if ($actions)
    <section class="mb-6" aria-labelledby="quick-actions">
        <h2 id="quick-actions" class="mb-3 flex items-center gap-2 text-xs font-bold tracking-wider text-slate-500 uppercase"><x-icon name="sparkles" class="h-4 w-4 text-accent-500" /> Quick actions</h2>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($actions as [$route, $label, $hint, $icon, $tone])
                <a href="{{ route($route) }}" wire:navigate class="action-tile group">
                    <div class="icon-chip tone-{{ $tone }} h-11 w-11 transition group-hover:scale-105"><x-icon :name="$icon" /></div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-slate-900">{{ $label }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $hint }}</div>
                    </div>
                    <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                </a>
            @endforeach
        </div>
    </section>
@endif
