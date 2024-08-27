<?php

namespace Fajarwz\LaravelReview\Traits;

use Fajarwz\LaravelReview\Models\Review;
use Fajarwz\LaravelReview\Models\ReviewSummary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait CanBeReviewed
{
    /**
     * Returns a collection of reviews received by this model.
     *
     * This method allows filtering reviews by a specific reviewer.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model  The reviewer model to filter reviews by (optional)
     */
    public function receivedReviews(?Model $model = null): HasMany
    {
        $reviews = $this->hasMany(Review::class, 'reviewable_id')
            ->where('reviewable_type', get_class($this));

        if ($model) {
            return $reviews->where('reviewer_type', get_class($model));
        }

        return $reviews;
    }

    /**
     * Check if the current model has received a review from the specified model.
     */
    public function hasReceivedReview(Model $model, bool $includeUnapproved = false): bool
    {
        $query = $this->receivedReviews($model)
            ->where('reviewer_id', $model->getKey());

        if ($includeUnapproved) {
            $query->withUnapproved();
        }

        return $query->exists();
    }

    /**
     * Get the current model received review of the specified model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     */
    public function getReceivedReview(Model $model, bool $includeUnapproved = false): ?Review
    {
        $query = $this->receivedReviews($model)
            ->where('reviewer_id', $model->getKey());

        if ($includeUnapproved) {
            $query->withUnapproved();
        }

        return $query->first();
    }

    /**
     * Query the current model latest received reviews of the specified model.
     */
    public function latestReceivedReviews(?Model $model = null): HasMany
    {
        return $this->receivedReviews($model)->orderByDesc('created_at');
    }

    /**
     * Query the current model top rated received reviews of the specified model.
     */
    public function topRatedReceivedReviews(?Model $model = null): HasMany
    {
        return $this->receivedReviews($model)->orderByDesc('rating');
    }

    /**
     * Returns the review summary for this model.
     */
    public function reviewSummary(): MorphOne
    {
        return $this->morphOne(ReviewSummary::class, 'reviewable');
    }

    /**
     * Increment the review summary for this model.
     *
     * @param  array  $params  The parameters needed.
     */
    public function updateReviewSummary(array $params): void
    {
        $rating = $params['rating'] ?? 0.0;
        $oldRating = $params['oldRating'] ?? 0.0;
        $isUpdate = $params['isUpdate'] ?? false;
        $decrement = $params['decrement'] ?? false;

        $summary = $this->reviewSummary()->firstOrCreate([]);

        if ($decrement) {
            $summary = $this->decrementReview($summary, $rating);
        } else {
            if ($isUpdate) {
                $summary = $this->updateExistingReview($summary, $rating, $oldRating);
            } else {
                $summary = $this->addNewReview($summary, $rating);
            }
        }

        $summary->save();
    }

    protected function decrementReview(ReviewSummary $summary, float $rating): ReviewSummary
    {
        if ($summary->review_count > 1) {
            $summary->review_count -= 1;
            $totalRating = $summary->average_rating * ($summary->review_count + 1);
            $summary->average_rating = $this->calculateAverageRating(
                $totalRating - $rating,
                $summary->review_count
            );
        } else {
            $summary->review_count = 0;
            $summary->average_rating = 0.0;
        }

        return $summary;
    }

    protected function updateExistingReview(ReviewSummary $summary, float $rating, float $oldRating): ReviewSummary
    {
        $summary->average_rating = $this->calculateAverageRating(
            ($summary->average_rating * $summary->review_count - $oldRating + $rating),
            $summary->review_count
        );

        return $summary;
    }

    protected function addNewReview(ReviewSummary $summary, float $rating): ReviewSummary
    {
        $summary->review_count += 1;
        $summary->average_rating = $this->calculateAverageRating(
            ($summary->average_rating * ($summary->review_count - 1) + $rating),
            $summary->review_count
        );

        return $summary;
    }

    protected function calculateAverageRating(float $totalRating, int $reviewCount): float
    {
        if ($reviewCount <= 0) {
            return 0.0;
        }

        return number_format($totalRating / $reviewCount, 2);
    }
}
