@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $values = \App\Models\Review::STAR_VALUES;
    $id = 'stars-'.\Illuminate\Support\Str::random(6);
@endphp

{{--
    Ten native radios (0.5 to 5 in half steps) drawn as half stars. The radios stay in the
    accessibility tree (sr-only) so keyboard arrow keys and screen readers work (research R11).
--}}
<fieldset
    x-data="{ value: @entangle($attributes->wire('model')), hover: null }"
    {{ $attributes->whereDoesntStartWith('wire:model')->class('space-y-2') }}
>
    <legend class="text-sm font-medium text-zinc-800 dark:text-white">{{ __('Rating') }}</legend>

    <div class="flex items-center gap-3" x-on:mouseleave="hover = null">
        <div class="inline-flex">
            @for ($i = 1; $i <= 5; $i++)
                <span class="relative inline-block size-8">
                    <flux:icon.star variant="outline" class="pointer-events-none absolute inset-0 size-8 text-zinc-300 dark:text-zinc-600" />

                    @foreach ([$i - 0.5, $i] as $half)
                        @php($value = (string) $half)
                        @continue(! in_array($value, $values, true))
                        <span
                            class="pointer-events-none absolute inset-0 overflow-hidden"
                            style="width: {{ $half == $i ? 100 : 50 }}%"
                            x-show="Number(hover ?? value ?? 0) >= {{ $half }}"
                            x-cloak
                        >
                            <flux:icon.star variant="solid" class="size-8 text-amber-400" />
                        </span>
                    @endforeach

                    @foreach ([$i - 0.5, $i] as $half)
                        @php($value = (string) $half)
                        @continue(! in_array($value, $values, true))
                        <label
                            class="absolute inset-y-0 {{ $half == $i ? 'right-0' : 'left-0' }} w-1/2 cursor-pointer"
                            x-on:mouseenter="hover = '{{ $value }}'"
                        >
                            <input
                                type="radio"
                                name="{{ $id }}"
                                value="{{ $value }}"
                                class="peer sr-only"
                                x-model="value"
                                data-test="star-{{ $value }}"
                            >
                            <span class="sr-only">{{ trans_choice(':count star|:count stars', $half == 1 ? 1 : 2, ['count' => $value]) }}</span>
                            <span class="absolute inset-0 rounded peer-focus-visible:ring-2 peer-focus-visible:ring-amber-500"></span>
                        </label>
                    @endforeach
                </span>
            @endfor
        </div>

        <span class="min-w-8 text-sm font-medium text-zinc-700 dark:text-zinc-300" x-text="hover ?? value ?? ''"></span>
    </div>

    <flux:error :name="$model" />
</fieldset>
