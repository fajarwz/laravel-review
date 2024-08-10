<?php

namespace Fajarwz\LaravelReview\Tests;

use Fajarwz\LaravelReview\Exceptions\DuplicateReviewException;
use Fajarwz\LaravelReview\Models\Review;
use Fajarwz\LaravelReview\Models\ReviewSummary;
use Fajarwz\LaravelReview\Tests\Models\Product;
use Fajarwz\LaravelReview\Tests\Models\User;
use DB;

class CanReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $otherUser = User::factory()->create();

        $reviews = [
            [
                'reviewer_id' => $this->user->id,
                'reviewer_type' => get_class($this->user),
                'reviewable_id' => $this->product->id,
                'reviewable_type' => get_class($this->product),
                'rating' => 4.5,
                'approved_at' => now(),
            ],
            [
                'reviewer_id' => $this->user->id,
                'reviewer_type' => get_class($this->user),
                'reviewable_id' => $otherUser->id,
                'reviewable_type' => get_class($otherUser),
                'rating' => 5,
                'approved_at' => now(),
            ],
        ];

        foreach ($reviews as $review) {
            DB::table('reviews')->insert($review);
            DB::table('review_summaries')->insert([
                'reviewable_id' => $review['reviewable_id'],
                'reviewable_type' => $review['reviewable_type'],
                'average_rating' => $review['rating'],
                'review_count' => 1,
            ]);
        }
    }

    public function test_a_reviewer_can_displays_its_given_reviews_to_all_models()
    {
        $reviews = $this->user->givenReviews()->get();
        $this->assertCount(2, $reviews);
    }

    public function test_a_reviewer_can_displays_its_given_reviews_to_a_specific_model()
    {
        $reviews = $this->user->givenReviews($this->product)->get();
        $this->assertCount(1, $reviews);
    }

    public function test_should_return_true_if_a_reviewer_has_reviewed_the_given_model()
    {
        $this->assertTrue($this->user->hasReviewed($this->product));
    }

    public function test_should_return_false_if_a_reviewer_has_not_reviewed_the_given_model()
    {
        $this->assertFalse($this->user->hasReviewed($this->user));
    }

    public function test_a_reviewer_can_review_the_given_model()
    {
        $newProduct = Product::factory()->create();

        $this->user->review($newProduct, 4, 'nice!!!');

        $this->assertEquals($newProduct->id, $this->user->givenReviews(new Product)->orderByDesc('id')->first()->reviewable_id);
    }

    public function test_it_can_review_the_same_model()
    {
        $newUser = User::factory()->create();

        $this->user->review($newUser, 4, 'nice!!!');

        $this->assertEquals($newUser->id, $this->user->givenReviews(new User)->orderByDesc('id')->first()->reviewable_id);
    }

    public function test_a_reviewer_can_not_review_a_model_that_has_already_been_reviewed_using_review_function()
    {
        $this->expectException(DuplicateReviewException::class);

        $newUser = User::factory()->create();

        $this->user->review($newUser, 4, 'nice!!!');
        $this->user->review($newUser, 5, 'nice!!!');
    }

    public function test_a_reviewer_can_update_a_review()
    {
        $product = $this->user->givenReviews(new Product)->first()->reviewable;

        $newRating = 3.5;
        $this->user->updateReview($product, $newRating, 'nice!!!');

        $this->assertEquals($this->user->givenReviews(new Product)->whereReviewableId($product->id)->first()->rating, $newRating);
    }
    
    public function test_a_review_summary_for_the_reviewable_model_updated_after_update_review()
    {
        $reviewSummary = ReviewSummary::where([
            'reviewable_id' => $this->product->id,
            'reviewable_type' => get_class($this->product),
        ])->first();

        $this->user->updateReview($this->product, 4, 'nice!!!');

        $this->assertTrue($reviewSummary->average_rating !== $this->product->reviewSummary->average_rating);
    }

    public function test_a_reviewer_can_unreview_a_review()
    {
        $newProduct = Product::factory()->create();

        $this->user->review($newProduct, 4);
        $this->user->unreview($newProduct);

        $this->assertFalse($this->user->givenReviews(new Product)->whereReviewableId($newProduct->id)->exists());
    }

    public function test_a_review_summary_display_correct_summary()
    {
        DB::table('reviews')->truncate();
        DB::table('review_summaries')->truncate();

        $userNumber = 100;
        $users = User::factory($userNumber)->create();
        $product = Product::factory()->create();
        $ratings = $this->getRandomRatings($userNumber, 1, 10);

        for ($i = 0; $i < $userNumber; $i++) {
            $users[$i]->review($product, $ratings[$i]);
        }

        $averageRating = round(array_sum($ratings) / count($ratings), 2);
        $productAverageRating = round($product->reviewSummary->average_rating, 2);

        $this->assertEquals($averageRating, $productAverageRating);
        $this->assertEquals($product->reviewSummary->review_count, $userNumber);
    }

    private function getRandomRatings($total, $min, $max)
    {
        $randomNumbers = [];

        for ($i = 0; $i < $total; $i++) {
            $randomNumbers[] = rand($min, $max);
        }

        return $randomNumbers;
    }
}
