<x-layouts::auth :title="__('Admin')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Admin')" :description="__('System totals')" />

        <dl class="flex flex-col gap-3">
            @foreach ([__('Users') => $totals['users'], __('Couples') => $totals['couples'], __('Reviews') => $totals['reviews']] as $label => $total)
                <div class="flex items-center justify-between">
                    <dt><flux:text>{{ $label }}</flux:text></dt>
                    <dd><flux:heading size="lg">{{ number_format($total) }}</flux:heading></dd>
                </div>
            @endforeach
        </dl>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <flux:button type="submit" class="w-full" data-test="admin-logout-button">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
