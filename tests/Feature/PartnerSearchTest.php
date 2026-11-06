<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle', 'email' => 'kyle@example.com']);
    $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry', 'email' => 'harry@example.com']);

    RateLimiter::clear('partner-search:'.$this->kyle->id);
});

test('guests are redirected to the login page', function () {
    $this->get(route('partner.search'))->assertRedirect(route('login'));
});

test('signed-in users can view the partner search page', function () {
    $this->actingAs($this->kyle)
        ->get(route('partner.search'))
        ->assertOk()
        ->assertSee('Find your partner');
});

test('a user can find their partner by exact username in any case', function () {
    $this->actingAs($this->kyle);

    Livewire::test('pages::partner.search')
        ->set('query', 'HARRY')
        ->call('search')
        ->assertHasNoErrors()
        ->assertSee('@harry')
        ->assertSee('Harry')
        ->assertSet('result', ['username' => 'harry', 'name' => 'Harry']);
});

test('search results never expose the email address', function () {
    $this->actingAs($this->kyle);

    $component = Livewire::test('pages::partner.search')
        ->set('query', 'harry')
        ->call('search')
        ->assertDontSee('harry@example.com');

    expect(json_encode($component->get('result')))->not->toContain('harry@example.com');
});

test('surrounding whitespace in the search term is ignored', function () {
    $this->actingAs($this->kyle);

    Livewire::test('pages::partner.search')
        ->set('query', '  harry  ')
        ->call('search')
        ->assertSee('@harry');
});

test('partial usernames do not match', function () {
    $this->actingAs($this->kyle);

    Livewire::test('pages::partner.search')
        ->set('query', 'har')
        ->call('search')
        ->assertSet('result', null)
        ->assertSee('No user found with that username.');
});

test('searching for your own username returns no result', function () {
    $this->actingAs($this->kyle);

    Livewire::test('pages::partner.search')
        ->set('query', 'kyle')
        ->call('search')
        ->assertSet('result', null)
        ->assertSee('No user found with that username.');
});

test('an empty search term is rejected without searching', function (string $query) {
    $this->actingAs($this->kyle);

    Livewire::test('pages::partner.search')
        ->set('query', $query)
        ->call('search')
        ->assertHasErrors('query')
        ->assertSet('searched', false);
})->with(['empty' => '', 'whitespace' => '   ']);

test('searches are rate limited per user', function () {
    $this->actingAs($this->kyle);

    $component = Livewire::test('pages::partner.search')->set('query', 'nobody');

    foreach (range(1, 30) as $ignored) {
        $component->call('search')->assertHasNoErrors();
    }

    $component->set('query', 'harry')
        ->call('search')
        ->assertHasErrors('query')
        ->assertSee('Too many searches')
        ->assertSet('result', null);
});
