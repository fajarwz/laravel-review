<?php

namespace Fajarwz\LaravelReview\Traits;

use DB;
use Fajarwz\LaravelReview\Exceptions\DuplicateReviewException;
use Fajarwz\LaravelReview\Exceptions\ReviewNotFoundException;
use Fajarwz\LaravelReview\Models\Review;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait CanReview
{
    /**
     * Returns a collection of reviews given by this model.
     *
     * This method allows filtering reviews by a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model  The model to filter reviews by (optional)
     */
    public function givenReviews(?Model $model = null): HasMany
    {
        $reviews = $this->hasMany(Review::class, 'reviewer_id')
            ->where('reviewer_type', get_class($this));

        if ($model) {
            return $reviews->where('reviewable_type', get_class($model));
        }

        return $reviews;
    }

    /**
     * Check if the current model has given a review for the specified model.
     */
    public function hasGivenReview(Model $model, bool $includeUnapproved = false): bool
    {
        $query = $this->givenReviews()
            ->where('reviewable_type', get_class($model))
            ->where('reviewable_id', $model->getKey());

        if ($includeUnapproved) {
            $query->withUnapproved();
        }

        return $query->exists();
    }

    /**
     * Get the current model given review for the specified model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     */
    public function getGivenReview(Model $model, bool $includeUnapproved = false): ?Review
    {
        $query = $this->givenReviews()
            ->where('reviewable_type', get_class($model))
            ->where('reviewable_id', $model->getKey());

        if ($includeUnapproved) {
            $query->withUnapproved();
        }

        return $query->first();
    }

    /**
     * Review a certain model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function review($model, float $rating, ?string $reviewContent = null, bool $isApproved = true): Review
    {
        if ($this->hasGivenReview($model, true)) {
            throw new DuplicateReviewException;
        }

        $data = [
            'reviewer_id' => $this->id,
            'reviewer_type' => get_class($this),
            'reviewable_id' => $model->getKey(),
            'reviewable_type' => $model->getMorphClass(),
            'rating' => $rating,
            'content' => $reviewContent,
            'approved_at' => $isApproved ? now() : null,
        ];

        return $this->saveReview($data);
    }

    /**
     * Update the review for a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function updateReview($model, float $newRating, ?string $newReview = null): Review
    {
        $data = [
            'reviewer_id' => $this->id,
            'reviewer_type' => get_class($this),
            'reviewable_id' => $model->getKey(),
            'reviewable_type' => $model->getMorphClass(),
            'rating' => $newRating,
            'content' => $newReview,
        ];

        return $this->saveReview($data, true);
    }

    /**
     * Unreview a certain model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function unreview($model): bool
    {
        if (! $this->hasGivenReview($model, true)) {
            throw new ReviewNotFoundException;
        }

        DB::transaction(function () use ($model) {
            $review = $this->givenReviews($model)->where('reviewable_id', $model->getKey())->first();
            $params = [
                'rating' => $review->rating,
                'decrement' => true,
            ];
            $model->updateReviewSummary($params);
            $review->delete();
        });

        return true;
    }

    /**
     * Save a review and update the summary.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function saveReview(array $data, bool $isUpdate = false): Review
    {
        return DB::transaction(function () use ($data, $isUpdate) {
            $review = Review::where('reviewer_id', $data['reviewer_id'])
                ->where('reviewer_type', $data['reviewer_type'])
                ->where('reviewable_id', $data['reviewable_id'])
                ->where('reviewable_type', $data['reviewable_type'])
                ->firstOrNew();

            $oldRating = $this->getOldRating($review, $isUpdate);

            $review->fill($data);
            $review->save();

            $this->handleUpdateReviewSummary($review, $data, $isUpdate, $oldRating);

            return $review;
        });
    }

    protected function getOldRating($review, $isUpdate)
    {
        $oldRating = 0.0;
        if ($isUpdate) {
            $oldRating = $review->rating;
        }

        return $oldRating;
    }

    protected function handleUpdateReviewSummary($review, $data, $isUpdate, $oldRating)
    {
        $isUnapprove = array_key_exists('approved_at', $data) && is_null($data['approved_at']);
        if ($isUnapprove) {
            return;
        }

        $params = [
            'rating' => $data['rating'],
            'oldRating' => $oldRating,
            'isUpdate' => $isUpdate,
        ];
        $review->reviewable->updateReviewSummary($params);
    }
}
