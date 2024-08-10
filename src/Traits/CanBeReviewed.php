<?php

namespace Fajarwz\LaravelReview\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Fajarwz\LaravelReview\Models\Review;
use Fajarwz\LaravelReview\Models\ReviewSummary;

trait CanBeReviewed
{
    /**
     * Returns a collection of reviews for this model.
     * This model acts as the reviewable entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function receivedReviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Returns the review summary for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function reviewSummary(): MorphOne
    {
        return $this->morphOne(ReviewSummary::class, 'reviewable');
    }

    /**
     * Increment the review summary for this model.
     *
     * @param float $rating The rating of the new review.
     * @return void
     */
    public function incrementReviewSummary(float $rating): void
    {
        $summary = $this->reviewSummary()->firstOrCreate();

        $currentReviewCount = $summary->review_count;
        $newReviewCount = $summary->review_count + 1;

        $summary->review_count = $newReviewCount;
        $summary->average_rating = (($currentReviewCount * $summary->average_rating) + $rating) / ($newReviewCount);

        $summary->save();
    }

    /**
     * Decrement the review summary for this model.
     *
     * @param float $rating The rating of the new review.
     * @return void
     */
    public function decrementReviewSummary(float $rating): void
    {
        $summary = $this->reviewSummary()->firstOrCreate();

        $currentReviewCount = $summary->review_count;
        $newReviewCount = $summary->review_count - 1;

        $summary->review_count = max(0, $newReviewCount);
        $summary->average_rating = $newReviewCount > 0 ? (($currentReviewCount * $summary->average_rating) - $rating) / $newReviewCount : 0;

        $summary->save();
    }
}