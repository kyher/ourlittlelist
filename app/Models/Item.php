<?php

namespace App\Models;

use App\Enums\ReviewCategory;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Something a couple has reviewed: a category and a title. At most one per couple, category
 * and normalised title (FR-004). Deleted with its couple, and when its last review is removed.
 *
 * @property int $id
 * @property int $couple_id
 * @property ReviewCategory $category
 * @property string $title
 * @property string $title_key
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Couple $couple
 * @property-read Collection<int, Review> $reviews
 * @property-read Collection<int, ReviewPrompt> $prompts
 */
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['couple_id', 'category', 'title', 'title_key', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ReviewCategory::class,
        ];
    }

    /**
     * The key used to spot duplicate titles: trimmed and lowercased.
     */
    public static function keyFor(string $title): string
    {
        return mb_strtolower(trim($title));
    }

    /**
     * Store the trimmed title and keep title_key in step with it.
     *
     * @return Attribute<string, string>
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => [
                'title' => trim($value),
                'title_key' => self::keyFor($value),
            ],
        );
    }

    /**
     * @return BelongsTo<Couple, $this>
     */
    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
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
    public function prompts(): HasMany
    {
        return $this->hasMany(ReviewPrompt::class);
    }

    public function reviewBy(User $user): ?Review
    {
        return $this->reviews()->where('user_id', $user->id)->first();
    }

    /**
     * Only the given couple's items. Every item lookup MUST go through this (Principle III).
     *
     * @param  Builder<Item>  $query
     */
    public function scopeForCouple(Builder $query, Couple $couple): void
    {
        $query->whereBelongsTo($couple);
    }
}
