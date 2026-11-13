<div>
    <x-page-header title="Website messages" subtitle="What visitors sent through the contact form." />

    <div class="space-y-2">
        @forelse ($messages as $message)
            <div wire:key="msg-{{ $message->id }}" @class(['card-pad p-4!', 'border-brand-500' => ! $message->read_at])>
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="text-sm">
                        <span class="font-semibold">{{ $message->name }}</span>
                        @unless ($message->read_at)<span class="badge badge-blue ms-1">New</span>@endunless
                        <div class="text-xs text-slate-500">
                            @if ($message->phone)<a class="underline" href="tel:{{ $message->phone }}">{{ $message->phone }}</a>@endif
                            @if ($message->email)<a class="ms-2 underline" href="mailto:{{ $message->email }}">{{ $message->email }}</a>@endif
                            <span class="ms-2">{{ $message->created_at->format('j M Y, g:i a') }}</span>
                        </div>
                    </div>
                    <div class="flex gap-1.5">
                        <button class="btn btn-sm" wire:click="toggleRead({{ $message->id }})">{{ $message->read_at ? 'Mark unread' : 'Mark read' }}</button>
                        <button class="btn btn-sm btn-danger" wire:click="delete({{ $message->id }})" wire:confirm="Delete this message?">Delete</button>
                    </div>
                </div>
                <p class="mt-2 text-sm whitespace-pre-line">{{ $message->message }}</p>
            </div>
        @empty
            <div class="card-pad text-sm text-slate-500">No messages yet.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $messages->links() }}</div>
</div>
