<?php

namespace Fajarwz\LaravelReview\Traits;

use Fajarwz\LaravelReview\Exceptions\DuplicateReviewException;
use Fajarwz\LaravelReview\Models\Review;
use DB;
use Fajarwz\LaravelReview\Exceptions\ReviewNotFoundException;
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public function hasGivenReview($model): bool
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
        if ($this->hasGivenReview($model)) {
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
        return $this->saveReview($data, true);
    }

    /**
     * Unreview a certain model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public function unreview($model): bool
    {
        if (!$this->hasGivenReview($model)) {
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
     * @param  bool                                 $isUpdate
     * @return \Fajarwz\LaravelReview\Models\Review
     */
    public function saveReview(array $data, bool $isUpdate = false): Review
    {
        return DB::transaction(function () use ($data, $isUpdate) {
            $review = Review::where('reviewer_id', $data['reviewer_id'])
                ->where('reviewer_type', $data['reviewer_type'])
                ->where('reviewable_id', $data['reviewable_id'])
                ->where('reviewable_type', $data['reviewable_type'])
                ->firstOrNew();

            $oldRating = 0.0;
            if ($isUpdate) {
                $oldRating = $review->rating;
            }
    
            $review->fill($data);
            $review->save();
    
            if ($data['approved_at']) {
                $params = [
                    'rating' => $data['rating'],
                    'oldRating' => $oldRating,
                    'isUpdate' => $isUpdate,
                ];
                $review->reviewable->updateReviewSummary($params);
            }
    
            return $review;
        });
    }
}