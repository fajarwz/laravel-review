<?php

namespace Fajarwz\LaravelReview\Traits;

use Fajarwz\LaravelReview\Exceptions\DuplicateReviewException;
use Fajarwz\LaravelReview\Models\Review;
use DB;
use Fajarwz\LaravelReview\Exceptions\ReviewNotFoundException;

trait CanReview
{
    /**
     * Relationship for models that this model currently reviewed.
     *
     * @param  null|\Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function givenReviews($model = null)
    {
        $reviews = $this->hasMany(Review::class, 'reviewer_id')
            ->where('reviewer_type', get_class($this));

        if ($model) {
            return $reviews->where('reviewable_type', get_class($model));
        }

        return $reviews;
    }

    /**
     * Check if the current model is rating another model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public function hasReviewed($model): bool
    {
        return $this->givenReviews()
            ->where('reviewable_type', get_class($model))
            ->where('reviewable_id', $model->getKey())
            ->exists();
    }

    /**
     * Review a certain model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  float    $rating
     * @param  string   $reviewContent
     * @param  bool     $isApproved
     * @return \Fajarwz\LaravelReview\Models\Review
     */
    public function review($model, float $rating, string $reviewContent = null, bool $isApproved = true): Review
    {
        if ($this->hasReviewed($model)) {
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
     * @param  float    $newRating
     * @param  string   $newReview
     * @param  bool     $isApproved
     * @return \Fajarwz\LaravelReview\Models\Review
     */
    public function updateReview($model, float $newRating, string $newReview = null, bool $isApproved = true): Review
    {
        $data = [
            'reviewer_id' => $this->id,
            'reviewer_type' => get_class($this),
            'reviewable_id' => $model->getKey(),
            'reviewable_type' => $model->getMorphClass(),
            'rating' => $newRating,
            'content' => $newReview,
            'approved_at' => $isApproved ? now() : null,
        ];
        return $this->saveReview($data);
    }

    /**
     * Unreview a certain model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public function unreview($model): bool
    {
        if (!$this->hasReviewed($model)) {
            throw new ReviewNotFoundException;
        }

        DB::transaction(function () use ($model) {
            $review = $this->givenReviews($model)->where('reviewable_id', $model->getKey())->first();
            $model->decrementReviewSummary($review->rating);
            $review->delete();
        });

        return true;
    }

    /**
     * Save a review and update the summary.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Fajarwz\LaravelReview\Models\Review
     */
    public function saveReview(array $data): Review
    {
        return DB::transaction(function () use ($data) {
            $review = Review::where('reviewer_id', $data['reviewer_id'])
                ->where('reviewer_type', $data['reviewer_type'])
                ->where('reviewable_id', $data['reviewable_id'])
                ->where('reviewable_type', $data['reviewable_type'])
                ->firstOrNew();
    
            $review->fill($data);
            $review->save();
    
            if ($data['approved_at']) {
                $reviewable = $review->reviewable;
                $reviewable->incrementReviewSummary($data['rating']);
            }
    
            return $review;
        });
    }
}