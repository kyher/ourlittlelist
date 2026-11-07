<?php

namespace App\Models;

use App\Actions\Couples\EndCouple;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property int|null $couple_id
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Couple|null $couple
 * @property-read Collection<int, Review> $reviews
 * @property-read Collection<int, ReviewPrompt> $reviewPrompts
 */
#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Deleting an account ends its couple, so the partner is never left in a one-member
     * couple and the couple's data is deleted (research R8).
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->couple_id !== null && $user->couple) {
                app(EndCouple::class)($user->couple);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Normalise the username to trimmed lowercase whenever it is set.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : Str::lower(trim($value)),
        );
    }

    /**
     * The couple this user belongs to. couple_id is only ever set by AcceptCoupleRequest
     * and cleared by deleting the couple, so it is deliberately not fillable.
     *
     * @return BelongsTo<Couple, $this>
     */
    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    public function isInCouple(): bool
    {
        return $this->couple_id !== null;
    }

    /**
     * The other member of this user's couple, or null when single.
     */
    public function partner(): ?User
    {
        if ($this->couple_id === null) {
            return null;
        }

        return User::query()
            ->where('couple_id', $this->couple_id)
            ->whereKeyNot($this->id)
            ->first();
    }

    /**
     * @return HasMany<CoupleRequest, $this>
     */
    public function sentCoupleRequests(): HasMany
    {
        return $this->hasMany(CoupleRequest::class, 'sender_id');
    }

    /**
     * @return HasMany<CoupleRequest, $this>
     */
    public function receivedCoupleRequests(): HasMany
    {
        return $this->hasMany(CoupleRequest::class, 'receiver_id');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<ReviewPrompt, $this>
     */
    public function reviewPrompts(): HasMany
    {
        return $this->hasMany(ReviewPrompt::class);
    }

    /**
     * How many prompts to review something are waiting for this user (FR-013).
     */
    public function openReviewPromptCount(): int
    {
        return $this->reviewPrompts()->open()->count();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
