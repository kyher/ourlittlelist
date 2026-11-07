@props(['stars'])

@php
    $stars = (float) $stars;
    $label = rtrim(rtrim(number_format($stars, 1), '0'), '.');
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1') }} role="img" aria-label="{{ __(':stars out of 5 stars', ['stars' => $label]) }}">
    <span class="inline-flex" aria-hidden="true">
        @for ($i = 1; $i <= 5; $i++)
            @php($fill = $stars >= $i ? 100 : ($stars >= $i - 0.5 ? 50 : 0))
            <span class="relative inline-block size-5">
                <flux:icon.star variant="outline" class="absolute inset-0 size-5 text-zinc-300 dark:text-zinc-600" />
                @if ($fill > 0)
                    <span class="absolute inset-0 overflow-hidden" style="width: {{ $fill }}%">
                        <flux:icon.star variant="solid" class="size-5 text-amber-400" />
                    </span>
                @endif
            </span>
        @endfor
    </span>
    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300" aria-hidden="true">{{ $label }}</span>
</span>
