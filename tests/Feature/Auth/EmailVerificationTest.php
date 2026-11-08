<?php

namespace Tests\Feature\Auth;

use App\Models\Couple;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::emailVerification());
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertOk();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function memberRoutes(): array
    {
        return [
            'partner search' => ['partner.search'],
            'couple' => ['couple.show'],
            'reviews' => ['reviews.index'],
            'new review' => ['reviews.create'],
            'appearance settings' => ['appearance.edit'],
            'security settings' => ['security.edit'],
        ];
    }

    #[DataProvider('memberRoutes')]
    public function test_unverified_users_are_sent_to_the_verification_notice(string $routeName): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route($routeName))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_users_cannot_open_a_review(): void
    {
        $user = User::factory()->unverified()->create();
        $item = Item::factory()->create();

        $this->actingAs($user)->get(route('reviews.show', $item->id))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_users_can_open_their_profile_settings(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_unverified_users_can_choose_a_username(): void
    {
        $user = User::factory()->unverified()->create(['username' => null]);

        $this->actingAs($user)->get(route('username.create'))->assertOk();
    }

    public function test_unverified_users_can_log_out(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
    }

    public function test_verified_users_are_not_asked_to_verify(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('partner.search'))->assertOk();
        $this->actingAs($user)->get(route('appearance.edit'))->assertOk();
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('partner.search', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_already_verified_user_visiting_verification_link_is_redirected_without_firing_event_again(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('partner.search', absolute: false).'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertNotDispatched(Verified::class);
    }

    public function test_a_signed_out_person_verifies_after_logging_in(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = $this->verificationUrl($user);

        $this->get($verificationUrl)->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect($verificationUrl);

        $this->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verifying_without_a_username_continues_to_choosing_one(): void
    {
        $user = User::factory()->unverified()->create(['username' => null]);

        $this->actingAs($user)->get($this->verificationUrl($user))
            ->assertRedirect(route('partner.search', absolute: false).'?verified=1');

        $this->get(route('partner.search'))->assertRedirect(route('username.create'));
    }

    public function test_an_open_page_keeps_working_while_verified(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->postLivewireUpdateFor($this->get(route('appearance.edit')))->assertOk();
    }

    public function test_an_open_page_stops_working_once_unverified(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $page = $this->get(route('appearance.edit'));

        $user->markEmailAsUnverified();

        $this->postLivewireUpdateFor($page)->assertRedirect(route('verification.notice'));
    }

    public function test_the_verify_page_shows_the_address_and_a_way_to_change_it(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee(route('profile.edit'), false)
            ->assertDontSee('just emailed');
    }

    public function test_a_new_link_can_be_requested_from_the_verify_page(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_requests_for_new_links_are_limited(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        foreach (range(1, 6) as $attempt) {
            $this->post(route('verification.send'))->assertRedirect();
        }

        $this->post(route('verification.send'))->assertStatus(429);
    }

    public function test_an_expired_link_leads_back_to_the_verify_page(): void
    {
        $user = User::factory()->unverified()->create();

        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($expiredUrl)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-invalid');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        $this->get(route('verification.notice'))->assertSee('That link has expired or is invalid');
    }

    public function test_a_link_for_another_account_is_refused(): void
    {
        $signedIn = User::factory()->unverified()->create();
        $linkOwner = User::factory()->unverified()->create();

        $this->actingAs($signedIn)->get($this->verificationUrl($linkOwner))->assertForbidden();

        $this->assertFalse($signedIn->fresh()->hasVerifiedEmail());
        $this->assertFalse($linkOwner->fresh()->hasVerifiedEmail());
    }

    public function test_a_link_for_a_previous_address_does_not_verify_the_new_one(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'new@example.com']);

        $staleUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('old@example.com')],
        );

        $this->actingAs($user)->get($staleUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_existing_unverified_accounts_verify_and_keep_their_data(): void
    {
        $unverified = User::factory()->unverified()->create();
        $partner = User::factory()->create();
        $couple = Couple::factory()->withMembers($unverified, $partner)->create();
        $review = Review::factory()->by($unverified)->create();
        $unverified->refresh();

        $this->actingAs($unverified)->get(route('reviews.index'))
            ->assertRedirect(route('verification.notice'));

        $this->get($this->verificationUrl($unverified));

        $this->actingAs($unverified->fresh())->get(route('reviews.index'))->assertOk();
        $this->assertSame($couple->id, $unverified->fresh()->couple_id);
        $this->assertModelExists($review);
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );
    }

    /**
     * Send a real Livewire update for the appearance page. Livewire::test() skips persistent
     * middleware, so the check on open pages can only be proven through the update endpoint.
     */
    private function postLivewireUpdateFor(TestResponse $page): TestResponse
    {
        preg_match_all('/wire:snapshot="([^"]+)"/', $page->getContent(), $matches);

        $snapshot = collect($matches[1])
            ->map(fn (string $attribute) => html_entity_decode($attribute))
            ->first(fn (string $json) => data_get(json_decode($json, true), 'memo.name') === 'pages::settings.appearance');

        $this->assertNotNull($snapshot, 'The appearance page did not render its Livewire component.');

        // These are the headers the Livewire client sends: a JSON body, without asking for JSON back.
        return $this->json('POST', app('livewire')->getUpdateUri(), [
            '_token' => csrf_token(),
            'components' => [['snapshot' => $snapshot, 'updates' => (object) [], 'calls' => []]],
        ], ['X-Livewire' => '1', 'Accept' => 'text/html']);
    }
}
