<?php

namespace Fajarwz\LaravelReview\Tests;

use DB;
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
