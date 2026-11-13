@props(['actions'])
{{-- Big buttons for the jobs done every day: [route, label, hint, icon, tone]. --}}
@if ($actions)
    <section class="mb-5" aria-labelledby="quick-actions">
        <h2 id="quick-actions" class="mb-2.5 flex items-center gap-2 text-sm font-semibold text-slate-700"><x-icon name="sparkles" class="h-4 w-4 text-accent-500" /> Quick actions</h2>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($actions as [$route, $label, $hint, $icon, $tone])
                <a href="{{ route($route) }}" wire:navigate class="action-tile">
                    <div class="icon-chip tone-{{ $tone }}"><x-icon :name="$icon" /></div>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-slate-900">{{ $label }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $hint }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
