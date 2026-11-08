<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-800 antialiased dark:bg-zinc-900 dark:text-zinc-100">
        <header class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-2">
                <x-app-logo-icon class="size-7 fill-current text-accent-content" />
                <span class="font-semibold text-zinc-900 dark:text-white">{{ config('app.name', 'Our Little List') }}</span>
            </div>

            @auth
                <flux:button variant="ghost" :href="route('partner.search')">
                    {{ __('Open :app', ['app' => config('app.name', 'Our Little List')]) }}
                </flux:button>
            @else
                <flux:button variant="ghost" :href="route('login')">{{ __('Log in') }}</flux:button>
            @endauth
        </header>

        <main class="mx-auto flex max-w-5xl flex-col gap-20 px-4 pt-6 pb-20 sm:px-6 lg:gap-28 lg:pt-16">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <section class="flex flex-col items-start gap-6">
                    <h1 class="text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl dark:text-white">
                        {{ __("Everything you've loved, together.") }}
                    </h1>

                    <p class="text-lg text-zinc-600 dark:text-zinc-300">
                        {{ __('A private little place for the two of you to review the films, shows, books, food and restaurants you share.') }}
                    </p>

                    <x-home-cta />
                </section>

                <section aria-labelledby="example-heading" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 id="example-heading" class="text-sm font-semibold text-accent-content">{{ __('Example') }}</h2>
                    <h3 class="mt-1 text-xl font-semibold text-zinc-900 dark:text-white">{{ __('Moonlight Over Marrow Lane') }}</h3>

                    <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700">
                        <div class="flex flex-col gap-1 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ __('You') }}</span>
                                <x-star-rating :stars="4.5" />
                            </div>
                            <p class="text-zinc-600 dark:text-zinc-300">{{ __('Cried twice. Would watch again tomorrow.') }}</p>
                        </div>

                        <div class="flex flex-col gap-1 pt-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ __('Your partner') }}</span>
                                <x-star-rating :stars="3" />
                            </div>
                            <p class="text-zinc-600 dark:text-zinc-300">{{ __('Lovely, but far too long. Good snacks though.') }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <section aria-labelledby="how-heading" class="flex flex-col gap-8">
                <h2 id="how-heading" class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('How it works') }}</h2>

                <ol class="grid gap-8 sm:grid-cols-3">
                    @foreach ([
                        [__('Create your account.'), __('It only takes a minute.')],
                        [__('Connect with your partner.'), __("Find them by username and send a request. They accept, and you're linked.")],
                        [__('Review things together.'), __('Rate what you share, write what you thought, and see how your opinions compare.')],
                    ] as [$step, $detail])
                        <li class="flex flex-col gap-3">
                            <span class="flex size-9 items-center justify-center rounded-full bg-accent font-semibold text-accent-foreground" aria-hidden="true">{{ $loop->iteration }}</span>
                            <p class="text-zinc-600 dark:text-zinc-300">
                                <strong class="font-semibold text-zinc-900 dark:text-white">{{ $step }}</strong>
                                {{ $detail }}
                            </p>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section aria-labelledby="categories-heading" class="flex flex-col gap-8">
                <h2 id="categories-heading" class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('For everything you do together') }}</h2>

                <ul class="flex flex-wrap gap-3">
                    @foreach (\App\Enums\ReviewCategory::cases() as $category)
                        <li class="flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-4 py-2 font-medium text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            <flux:icon :name="$category->icon()" class="size-5 text-accent-content" />
                            {{ $category->label() }}
                        </li>
                    @endforeach
                </ul>
            </section>

            <section aria-labelledby="privacy-heading" class="flex flex-col gap-3 rounded-2xl bg-accent p-8 text-accent-foreground sm:p-12">
                <h2 id="privacy-heading" class="text-3xl font-semibold tracking-tight">{{ __('Just for the two of you') }}</h2>
                <p class="max-w-2xl text-lg">{{ __('Your reviews are visible only to you and your partner. Nobody else can read them.') }}</p>
            </section>

            <section aria-labelledby="closing-heading" class="flex flex-col items-center gap-6 text-center">
                <h2 id="closing-heading" class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('Start your little list today') }}</h2>

                <x-home-cta />
            </section>
        </main>

        @fluxScripts
    </body>
</html>
