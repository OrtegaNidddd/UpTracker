@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-xs text-rose-600 space-y-1 font-medium mt-1.5']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9" stroke-width="2"/>
                    <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="12" cy="16" r="0.75" fill="currentColor" stroke-width="1.5"/>
                </svg>
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
