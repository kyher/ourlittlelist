@php($maskId = 'logo-mask-'.\Illuminate\Support\Str::random(8))
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" data-logo="our-little-list" {{ $attributes }}>
    <defs>
        <mask id="{{ $maskId }}">
            <rect width="24" height="24" fill="white" />
            {{-- Front heart, widened by its stroke, cut out of the back heart to leave a gap between them. --}}
            <path
                transform="translate(7.6 6.6) scale(0.95)"
                fill="black"
                stroke="black"
                stroke-width="3"
                stroke-linejoin="round"
                d="M8 15C8 15 0 10 0 4.5 0 2 2 0 4.5 0 6 0 7.3.8 8 2 8.7.8 10 0 11.5 0 14 0 16 2 16 4.5 16 10 8 15 8 15Z"
            />
        </mask>
    </defs>
    {{-- Back heart (upper left) --}}
    <path
        transform="translate(1 2) scale(0.85)"
        mask="url(#{{ $maskId }})"
        fill="currentColor"
        d="M8 15C8 15 0 10 0 4.5 0 2 2 0 4.5 0 6 0 7.3.8 8 2 8.7.8 10 0 11.5 0 14 0 16 2 16 4.5 16 10 8 15 8 15Z"
    />
    {{-- Front heart (lower right) --}}
    <path
        transform="translate(7.6 6.6) scale(0.95)"
        fill="currentColor"
        d="M8 15C8 15 0 10 0 4.5 0 2 2 0 4.5 0 6 0 7.3.8 8 2 8.7.8 10 0 11.5 0 14 0 16 2 16 4.5 16 10 8 15 8 15Z"
    />
</svg>
