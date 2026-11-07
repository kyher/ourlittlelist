<?php

namespace App\Models;

use App\Enums\CoupleRequestStatus;
use Database\Factories\CoupleRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An invitation from one user (sender) to another (receiver) to form a couple.
 *
 * @property int $id
 * @property int $sender_id
 * @property int $receiver_id
 * @property CoupleRequestStatus $status
 * @property Carbon|null $responded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $sender
 * @property-read User $receiver
 */
#[Fillable(['sender_id', 'receiver_id', 'status', 'responded_at'])]
class CoupleRequest extends Model
{
    /** @use HasFactory<CoupleRequestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CoupleRequestStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * @param  Builder<CoupleRequest>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', CoupleRequestStatus::Pending);
    }

    /**
     * Move the request out of pending into a final status.
     */
    public function resolve(CoupleRequestStatus $status): void
    {
        $this->forceFill(['status' => $status, 'responded_at' => now()])->save();
    }
}
