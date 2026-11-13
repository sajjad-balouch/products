<div>
    @if ($staff)
        <div class="card-pad mb-4 flex flex-wrap items-center justify-between gap-3"
             x-data="{
                 busy: false,
                 send(action) {
                     this.busy = true;
                     const go = (lat, lng) => $wire.call(action, lat, lng).finally(() => this.busy = false);
                     if (! @js($needsLocation) || ! navigator.geolocation) return go(null, null);
                     navigator.geolocation.getCurrentPosition(p => go(p.coords.latitude, p.coords.longitude), () => go(null, null), { enableHighAccuracy: true, timeout: 15000 });
                 },
             }">
            <div class="text-sm">
                <div class="font-semibold">My attendance today</div>
                <div class="text-slate-500">
                    @if (! $today?->check_in)
                        Duty starts at {{ $staff->dutyStart($school) }}. Not checked in yet.
                    @else
                        Checked in at {{ $today->check_in }}{{ $today->status === 'L' ? ' (late)' : '' }}{{ $today->check_out ? ', checked out at '.$today->check_out : '' }}.
                    @endif
                    @if ($school->strict_attendance)<span class="block text-xs">Works only at school{{ $needsLocation ? '. Your phone will ask to share its location.' : ', on the school network.' }}</span>@endif
                </div>
            </div>
            @if (! $today?->check_in)
                <button class="btn btn-primary" :disabled="busy" @click="send('checkIn')">Check in now</button>
            @elseif (! $today->check_out)
                <button class="btn" :disabled="busy" @click="confirm('Check out for today?') && send('checkOut')">Check out</button>
            @else
                <span class="badge badge-green">Done for today</span>
            @endif
        </div>
    @endif
</div>
