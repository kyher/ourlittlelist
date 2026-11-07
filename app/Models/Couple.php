<?php

namespace App\Models;

use Database\Factories\CoupleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Exactly two users who have both agreed to connect. The members are the users whose
 * couple_id points here. Deleting a couple ends it and deletes all data it owns: every
 * couple-owned table MUST reference couples.id with cascadeOnDelete (FR-017).
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, User> $members
 */
class Couple extends Model
{
    /** @use HasFactory<CoupleFactory> */
    use HasFactory;

    /**
     * @return HasMany<User, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The member of this couple who is not the given user.
     */
    public function partnerOf(User $user): User
    {
        return $this->members()->whereKeyNot($user->id)->firstOrFail();
    }
}
