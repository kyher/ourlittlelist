<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <main class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 text-center md:p-10">
            <x-app-logo-icon class="size-12 fill-current text-black dark:text-white" />

            <div class="flex max-w-md flex-col gap-2">
                <flux:heading size="xl" level="1">{{ config('app.name', 'Our Little List') }}</flux:heading>
                <flux:text>
                    {{ __('A shared list for the two of you to review films, series, books, food and restaurants together.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <flux:button variant="primary" :href="route('partner.search')">
                        {{ __('Open :app', ['app' => config('app.name', 'Our Little List')]) }}
                    </flux:button>
                @else
                    <flux:button variant="primary" :href="route('login')">{{ __('Log in') }}</flux:button>

                    @if (Route::has('register'))
                        <flux:button :href="route('register')">{{ __('Register') }}</flux:button>
                    @endif
                @endauth
            </div>
        </main>

        @fluxScripts
    </body>
</html>
