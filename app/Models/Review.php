<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One member's review of one item. The rating is stored in half-stars, 1 to 10 (research R3).
 *
 * @property int $id
 * @property int $item_id
 * @property int $user_id
 * @property int $rating
 * @property string $body
 * @property Carbon|null $edited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Item $item
 * @property-read User $author
 */
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * The allowed ratings, in stars (FR-006).
     */
    public const STAR_VALUES = ['0.5', '1', '1.5', '2', '2.5', '3', '3.5', '4', '4.5', '5'];

    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'rating', 'body'];

    /**
     * Creating or editing a review bumps the item, which orders the list (research R12).
     *
     * @var list<string>
     */
    protected $touches = ['item'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'edited_at' => 'datetime',
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function stars(): float
    {
        return $this->rating / 2;
    }

    /**
     * The stars as one of STAR_VALUES, e.g. "4.5" or "4".
     */
    public function starsValue(): string
    {
        return (string) $this->stars();
    }

    public static function starsToRating(string|float $stars): int
    {
        return (int) round(((float) $stars) * 2);
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }
}
