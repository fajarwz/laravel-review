<?php

namespace Fajarwz\LaravelReview\Tests;

use DB;
use Fajarwz\LaravelReview\Models\Review;
use Fajarwz\LaravelReview\Tests\Models\Mentee;
use Fajarwz\LaravelReview\Tests\Models\Mentor;

class CanBeReviewedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $otherMentor = Mentor::factory()->create();

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
                'reviewer_id' => $otherMentor->id,
                'reviewer_type' => get_class($otherMentor),
                'reviewable_id' => $this->mentor->id,
                'reviewable_type' => get_class($this->mentor),
                'rating' => 5,
                'approved_at' => now(),
            ],
        ];

        DB::table('review_summaries')->insert([
            'reviewable_id' => $reviews[0]['reviewable_id'],
            'reviewable_type' => $reviews[0]['reviewable_type'],
            'average_rating' => ($reviews[0]['rating'] + $reviews[1]['rating']) / count($reviews),
            'review_count' => count($reviews),
        ]);

        foreach ($reviews as $review) {
            DB::table('reviews')->insert($review);
        }
    }

    public function test_a_reviewable_can_display_its_received_reviews_from_all_models()
    {
        $reviews = $this->mentor->receivedReviews()->get();
        $this->assertCount(2, $reviews);
    }

    public function test_a_reviewable_can_display_its_given_reviews_from_a_specific_model()
    {
        $reviews = $this->mentor->receivedReviews($this->mentee)->get();
        $this->assertCount(1, $reviews);
    }

    public function test_hasReceivedReview_returns_true_if_a_reviewable_has_been_reviewed_by_the_given_model()
    {
        $this->assertTrue($this->mentor->hasReceivedReview($this->mentee));
    }

    public function test_hasReceivedReview_returns_false_if_a_reviewable_has_not_been_reviewed_by_the_given_model()
    {
        $otherMentee = Mentee::factory()->create();
        $this->assertFalse($this->mentor->hasReceivedReview($otherMentee));
    }

    public function test_hasReceivedReview_returns_true_with_includeUnapproved_true_if_a_reviewable_has_unapproved_review_by_the_given_model()
    {
        $otherMentee = Mentee::factory()->create();

        DB::table('reviews')->insert([
            'reviewer_id' => $otherMentee->id,
            'reviewer_type' => get_class($otherMentee),
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
            'rating' => 3,
            'approved_at' => null,
        ]);

        $this->assertTrue($this->mentor->hasReceivedReview($otherMentee, true));
    }

    public function test_hasReceivedReview_returns_false_with_includeUnapproved_false_if_a_reviewable_has_unapproved_review_by_the_given_model()
    {
        $otherMentee = Mentee::factory()->create();

        DB::table('reviews')->insert([
            'reviewer_id' => $otherMentee->id,
            'reviewer_type' => get_class($otherMentee),
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
            'rating' => 3,
            'approved_at' => null,
        ]);

        $this->assertFalse($this->mentor->hasReceivedReview($otherMentee));
    }

    public function test_getReceivedReview_retrieves_only_approved_review_by_default()
    {
        $review = $this->mentor->getReceivedReview($this->mentee);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals($this->mentee->id, $review->reviewer_id);
        $this->assertEquals(4.5, $review->rating);
    }

    public function test_getReceivedReview_returns_null_for_unapproved_review_by_default()
    {
        $newMentee = Mentor::factory()->create();

        DB::table('reviews')->insert([
            'reviewer_id' => $newMentee->id,
            'reviewer_type' => get_class($newMentee),
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
            'rating' => 3,
            'approved_at' => null,
        ]);

        $review = $this->mentor->getReceivedReview($newMentee);

        $this->assertNull($review);
    }

    public function test_getReceivedReview_can_retrieve_unapproved_review_when_includeUnapproved_is_true()
    {
        $newMentee = Mentee::factory()->create();

        DB::table('reviews')->insert([
            'reviewer_id' => $newMentee->id,
            'reviewer_type' => get_class($newMentee),
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
            'rating' => 3,
            'approved_at' => null,
        ]);

        $review = $this->mentor->getReceivedReview($newMentee, true);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals($newMentee->id, $review->reviewer_id);
        $this->assertEquals(3, $review->rating);
    }

    public function test_getLatestReceivedReviews_retrieves_latest_approved_reviews_by_default()
    {
        $newMentee = Mentor::factory()->create();

        $newReview = $newMentee->review($this->mentor, 4, 'nice!!!');

        $reviews = $this->mentor->getLatestReceivedReviews();

        $this->assertCount(3, $reviews);
        $this->assertEquals($reviews->first()->id, $newReview->id);
    }

    public function test_getLatestReceivedReviews_not_returning_unapproved_reviews_by_default()
    {
        $newMentee = Mentor::factory()->create();

        $newReview = $newMentee->review($this->mentor, 4, 'nice!!!', isApproved: false);

        $reviews = $this->mentor->getLatestReceivedReviews();

        $this->assertCount(2, $reviews);
        $this->assertNotEquals($reviews->first()->id, $newReview->id);
    }

    public function test_getLatestReceivedReviews_can_retrieves_unapproved_reviews_when_includeUnapproved_is_true()
    {
        $newMentee = Mentor::factory()->create();

        $newReview = $newMentee->review($this->mentor, 4, 'nice!!!', isApproved: false);

        $reviews = $this->mentor->getLatestReceivedReviews(includeUnapproved: true);

        $this->assertCount(3, $reviews);
        $this->assertEquals($reviews->first()->id, $newReview->id);
    }

    public function test_a_reviewable_can_display_its_review_summary()
    {
        $this->assertTrue($this->mentor->reviewSummary->exists());
    }

    public function test_updateReviewSummary_should_increase_summary_correctly_when_a_review_is_added()
    {
        $otherMentee = Mentee::factory()->create();
        $oldReviewSummary = $this->mentor->reviewSummary;

        $otherMentee->review($this->mentor, 4);
        $newReviewSummary = $this->mentor->reviewSummary->fresh();

        $this->assertEquals(2, $oldReviewSummary->review_count);
        $this->assertEquals(3, $newReviewSummary->review_count);

        $this->assertEquals(4.75, $oldReviewSummary->average_rating);
        $this->assertEquals(4.5, $newReviewSummary->average_rating);
    }

    public function test_updateReviewSummary_should_decrease_summary_correctly_when_a_review_is_unreviewed()
    {
        $oldReviewSummary = $this->mentor->reviewSummary;

        $this->mentee->unreview($this->mentor);
        $newReviewSummary = $this->mentor->reviewSummary->fresh();

        $this->assertEquals(2, $oldReviewSummary->review_count);
        $this->assertEquals(1, $newReviewSummary->review_count);

        $this->assertEquals(4.75, $oldReviewSummary->average_rating);
        $this->assertEquals(5.0, $newReviewSummary->average_rating);
    }

    public function test_updateReviewSummary_should_update_summary_correctly_when_a_review_is_updated()
    {
        $oldReviewSummary = $this->mentor->reviewSummary;

        $this->mentee->updateReview($this->mentor, 4);
        $newReviewSummary = $this->mentor->reviewSummary->fresh();

        $this->assertEquals(2, $oldReviewSummary->review_count);
        $this->assertEquals(2, $newReviewSummary->review_count);

        $this->assertEquals(4.75, $oldReviewSummary->average_rating);
        $this->assertEquals(4.5, $newReviewSummary->average_rating);
    }

    public function test_updateReviewSummary_should_update_summary_correctly_when_a_review_is_approved()
    {
        $newMentee = Mentee::factory()->create();
        $newMentee->review($this->mentor, 4, 'nice!!!', false);

        $oldReviewSummary = $this->mentor->reviewSummary;

        $newMentee->givenReviews($this->mentor)->withUnapproved()->whereReviewableId($this->mentor->id)->first()->approve();
        $newReviewSummary = $this->mentor->reviewSummary->fresh();

        $this->assertEquals(2, $oldReviewSummary->review_count);
        $this->assertEquals(3, $newReviewSummary->review_count);

        $this->assertEquals(4.75, $oldReviewSummary->average_rating);
        $this->assertEquals(4.5, $newReviewSummary->average_rating);
    }

    public function test_updateReviewSummary_should_update_summary_correctly_when_a_review_is_unapproved()
    {
        $oldReviewSummary = $this->mentor->reviewSummary;

        $this->mentee->givenReviews($this->mentor)->whereReviewableId($this->mentor->id)->first()->unapprove();
        $newReviewSummary = $this->mentor->reviewSummary->fresh();

        $this->assertEquals(2, $oldReviewSummary->review_count);
        $this->assertEquals(1, $newReviewSummary->review_count);

        $this->assertEquals(4.75, $oldReviewSummary->average_rating);
        $this->assertEquals(5.0, $newReviewSummary->average_rating);
    }
}
