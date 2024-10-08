<?php

namespace Fajarwz\LaravelReview\Models;

use Fajarwz\LaravelReview\Scopes\ApprovedReviewsScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property float $rating
 */
class Review extends Model
{
    protected $fillable = [
        'reviewer_id',
        'reviewer_type',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'content',
        'approved_at',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ApprovedReviewsScope);
    }

    /**
     * Scope a query to include unapproved reviews.
     */
    public function scopeWithUnapproved(Builder $query): void
    {
        $query->withoutGlobalScope(ApprovedReviewsScope::class);
    }

    /**
     * Checks if the review is approved.
     *
     * A review is considered approved if the `approved_at` timestamp is not null.
     *
     * @return bool True if the review is approved, false otherwise.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Approves a review.
     *
     * Sets the `approved_at` timestamp to indicate approval and updates the review summary.
     */
    public function approve(): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        DB::transaction(function () {
            $this->approved_at = now();
            $this->save();

            $params = [
                'rating' => $this->rating,
            ];
            /** @phpstan-ignore-next-line */
            $this->reviewable->updateReviewSummary($params);
        });

        return true;
    }

    /**
     * Unapproves a review.
     *
     * Sets the `approved_at` timestamp to null and updates the review summary.
     */
    public function unapprove(): bool
    {
        if (! $this->isApproved()) {
            return false;
        }

        DB::transaction(function () {
            $this->approved_at = null;
            $this->save();

            $params = [
                'rating' => $this->rating,
                'decrement' => true,
            ];
            /** @phpstan-ignore-next-line */
            $this->reviewable->updateReviewSummary($params);
        });

        return true;
    }

    /**
     * Get the owning reviewer model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function reviewer()
    {
        return $this->morphTo();
    }

    /**
     * Get the owning reviewable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function reviewable()
    {
        return $this->morphTo();
    }
}
