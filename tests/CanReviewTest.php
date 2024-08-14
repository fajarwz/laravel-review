<?php

namespace Fajarwz\LaravelReview\Tests;

use DB;
use Fajarwz\LaravelReview\Exceptions\DuplicateReviewException;
use Fajarwz\LaravelReview\Exceptions\ReviewNotFoundException;
use Fajarwz\LaravelReview\Models\Review;
use Fajarwz\LaravelReview\Models\ReviewSummary;
use Fajarwz\LaravelReview\Tests\Models\Mentee;
use Fajarwz\LaravelReview\Tests\Models\Mentor;

class CanReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $otherMentee = Mentee::factory()->create();

        $reviews = [
            [
                'reviewer_id' => $this->mentee->id,
                'reviewer_type' => get_class($this->mentee),
                'reviewable_id' => $this->mentor->id,
                'reviewable_type' => get_class($this->mentor),
                'rating' => 4.5,
                'approved_at' => now(),
            ],
            [
                'reviewer_id' => $this->mentee->id,
                'reviewer_type' => get_class($this->mentee),
                'reviewable_id' => $otherMentee->id,
                'reviewable_type' => get_class($otherMentee),
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

    public function test_a_reviewer_can_display_its_given_reviews_to_all_models()
    {
        $reviews = $this->mentee->givenReviews()->get();
        $this->assertCount(2, $reviews);
    }

    public function test_a_reviewer_can_display_its_given_reviews_to_a_specific_model()
    {
        $reviews = $this->mentee->givenReviews($this->mentor)->get();
        $this->assertCount(1, $reviews);
    }

    public function test_hasGivenReview_returns_true_if_a_reviewer_has_reviewed_the_given_model()
    {
        $this->assertTrue($this->mentee->hasGivenReview($this->mentor));
    }

    public function test_hasGivenReview_returns_false_if_a_reviewer_has_not_reviewed_the_given_model()
    {
        $otherMentor = Mentor::factory()->create();
        $this->assertFalse($this->mentee->hasGivenReview($otherMentor));
    }

    public function test_hasGivenReview_returns_true_with_includeUnapproved_true_if_a_reviewer_has_unapproved_review_for_the_given_model()
    {
        $otherMentor = Mentor::factory()->create();

        DB::table('reviews')->insert([
            'reviewer_id' => $this->mentee->id,
            'reviewer_type' => get_class($this->mentee),
            'reviewable_id' => $otherMentor->id,
            'reviewable_type' => get_class($otherMentor),
            'rating' => 3,
            'approved_at' => null,
        ]);

        $this->assertTrue($this->mentee->hasGivenReview($otherMentor, true));
    }

    public function test_hasGivenReview_returns_false_with_includeUnapproved_false_if_a_reviewer_has_unapproved_review_for_the_given_model()
    {
        $otherMentor = Mentor::factory()->create();

        DB::table('reviews')->insert([
            'reviewer_id' => $this->mentee->id,
            'reviewer_type' => get_class($this->mentee),
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
            'rating' => 3,
            'approved_at' => null,
        ]);

        $this->assertFalse($this->mentee->hasGivenReview($otherMentor));
    }

    public function test_getGivenReview_retrieves_only_approved_review_by_default()
    {
        $review = $this->mentee->getGivenReview($this->mentor);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals($this->mentor->id, $review->reviewable_id);
        $this->assertEquals(4.5, $review->rating);
    }

    public function test_getGivenReview_returns_null_for_unapproved_review_by_default()
    {
        $newMentor = Mentor::factory()->create();

        DB::table('reviews')->insert([
            'reviewer_id' => $this->mentee->id,
            'reviewer_type' => get_class($this->mentee),
            'reviewable_id' => $newMentor->id,
            'reviewable_type' => get_class($newMentor),
            'rating' => 3,
            'approved_at' => null,
        ]);

        $review = $this->mentee->getGivenReview($newMentor);

        $this->assertNull($review);
    }

    public function test_getGivenReview_can_retrieve_unapproved_review_when_includeUnapproved_is_true()
    {
        $newMentor = Mentor::factory()->create();

        DB::table('reviews')->insert([
            'reviewer_id' => $this->mentee->id,
            'reviewer_type' => get_class($this->mentee),
            'reviewable_id' => $newMentor->id,
            'reviewable_type' => get_class($newMentor),
            'rating' => 3,
            'approved_at' => null,
        ]);

        $review = $this->mentee->getGivenReview($newMentor, true);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals($newMentor->id, $review->reviewable_id);
        $this->assertEquals(3, $review->rating);
    }

    public function test_a_reviewer_can_review_the_given_model()
    {
        $newMentor = Mentor::factory()->create();

        $this->mentee->review($newMentor, 4, 'nice!!!');

        $this->assertEquals($newMentor->id, $this->mentee->givenReviews(new Mentor)->orderByDesc('id')->first()->reviewable_id);
    }

    public function test_it_can_review_the_same_model()
    {
        $newMentee = Mentee::factory()->create();

        $this->mentee->review($newMentee, 4, 'nice!!!');

        $this->assertEquals($newMentee->id, $this->mentee->givenReviews(new Mentee)->orderByDesc('id')->first()->reviewable_id);
    }

    public function test_a_reviewer_can_not_review_a_model_that_has_already_been_reviewed_using_review_function()
    {
        $this->expectException(DuplicateReviewException::class);

        $newMentee = Mentee::factory()->create();

        $this->mentee->review($newMentee, 4, 'nice!!!');
        $this->mentee->review($newMentee, 5, 'nice!!!');
    }

    public function test_a_reviewer_can_update_a_review()
    {
        $mentor = $this->mentee->givenReviews(new Mentor)->first()->reviewable;

        $newRating = 3.5;
        $this->mentee->updateReview($mentor, $newRating, 'nice!!!');

        $this->assertEquals($this->mentee->givenReviews(new Mentor)->whereReviewableId($mentor->id)->first()->rating, $newRating);
    }

    public function test_a_review_summary_for_the_reviewable_model_updated_after_update_review()
    {
        $reviewSummary = ReviewSummary::where([
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
        ])->first();

        $this->mentee->updateReview($this->mentor, 4, 'nice!!!');

        $this->assertTrue($reviewSummary->average_rating !== $this->mentor->reviewSummary->average_rating);
    }

    public function test_a_reviewer_can_unreview_a_review()
    {
        $newMentor = Mentor::factory()->create();

        $this->mentee->review($newMentor, 4);
        $this->mentee->unreview($newMentor);

        $this->assertFalse($this->mentee->givenReviews(new Mentor)->whereReviewableId($newMentor->id)->exists());
    }

    public function test_a_reviewer_can_not_unreview_a_review_that_does_not_exists()
    {
        $this->expectException(ReviewNotFoundException::class);

        $newMentee = Mentee::factory()->create();

        $this->mentee->unreview($newMentee);
    }

    public function test_a_review_summary_for_the_reviewable_model_updated_after_an_unreview()
    {
        $reviewSummary = ReviewSummary::where([
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
        ])->first();

        $this->mentee->unreview($this->mentor);

        $this->assertTrue($reviewSummary->average_rating !== $this->mentor->reviewSummary->average_rating);
    }

    public function test_a_review_summary_should_display_correct_summary()
    {
        DB::table('reviews')->truncate();
        DB::table('review_summaries')->truncate();

        $menteeNumber = 100;
        $mentees = Mentee::factory($menteeNumber)->create();
        $mentor = Mentor::factory()->create();
        $ratings = $this->getRandomRatings($menteeNumber, 1, 10);

        for ($i = 0; $i < $menteeNumber; $i++) {
            $mentees[$i]->review($mentor, $ratings[$i]);
        }

        $averageRating = round(array_sum($ratings) / count($ratings), 2);
        $mentorAverageRating = round($mentor->reviewSummary->average_rating, 2);

        // Compare with a delta to account for minor rounding differences
        $this->assertEqualsWithDelta($averageRating, $mentorAverageRating, 0.1);
        $this->assertEquals($mentor->reviewSummary->review_count, $menteeNumber);
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
