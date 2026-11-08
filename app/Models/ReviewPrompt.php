<?php

namespace App\Models;

use Database\Factories\ReviewPromptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Asks one member to review an item their partner reviewed first (FR-013). Deleted when they
 * review it; dismissed_at is set when they dismiss it (research R6).
 *
 * @property int $id
 * @property int $item_id
 * @property int $user_id
 * @property Carbon|null $dismissed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Item $item
 * @property-read User $recipient
 */
class ReviewPrompt extends Model
{
    /** @use HasFactory<ReviewPromptFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['user_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<ReviewPrompt>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('dismissed_at');
    }
}
