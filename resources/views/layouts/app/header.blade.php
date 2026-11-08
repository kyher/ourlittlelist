<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('partner.search') }}" wire:navigate />

            @php($promptCount = auth()->user()->isInCouple() ? auth()->user()->openReviewPromptCount() : 0)

            <flux:navbar class="-mb-px max-lg:hidden">
                @if (auth()->user()->isInCouple())
                    <flux:navbar.item icon="star" :href="route('reviews.index')" :current="request()->routeIs('reviews.*')" :badge="$promptCount ?: null" wire:navigate>
                        {{ __('Reviews') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="heart" :href="route('couple.show')" :current="request()->routeIs('couple.show')" wire:navigate>
                        {{ __('Your partner') }}
                    </flux:navbar.item>
                @else
                    <flux:navbar.item icon="magnifying-glass" :href="route('partner.search')" :current="request()->routeIs('partner.search')" wire:navigate>
                        {{ __('Find your partner') }}
                    </flux:navbar.item>
                @endif
            </flux:navbar>

            <flux:spacer />

            <x-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('partner.search') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @if (auth()->user()->isInCouple())
                    <flux:sidebar.item icon="star" :href="route('reviews.index')" :current="request()->routeIs('reviews.*')" :badge="$promptCount ?: null" wire:navigate>
                        {{ __('Reviews') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="heart" :href="route('couple.show')" :current="request()->routeIs('couple.show')" wire:navigate>
                        {{ __('Your partner') }}
                    </flux:sidebar.item>
                @else
                    <flux:sidebar.item icon="magnifying-glass" :href="route('partner.search')" :current="request()->routeIs('partner.search')" wire:navigate>
                        {{ __('Find your partner') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
