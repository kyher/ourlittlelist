@auth
    <flux:button variant="primary" :href="route('partner.search')">
        {{ __('Open :app', ['app' => config('app.name', 'Our Little List')]) }}
    </flux:button>
@else
    @if (\Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::registration()))
        <flux:button variant="primary" :href="route('register')">{{ __('Create your account') }}</flux:button>
    @else
        <flux:button variant="primary" :href="route('login')">{{ __('Log in') }}</flux:button>
    @endif
@endauth
