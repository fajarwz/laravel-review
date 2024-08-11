<?php

namespace Fajarwz\LaravelReview\Tests;

use Carbon\Carbon;
use Fajarwz\LaravelReview\Models\Review;
use Fajarwz\LaravelReview\Tests\Models\Mentee;
use Fajarwz\LaravelReview\Tests\Models\Mentor;

class ReviewTest extends TestCase
{
    protected $approvedReview;

    protected $unapprovedReview;

    protected function setUp(): void
    {
        parent::setUp();

        $otherMentee = Mentee::factory()->create();

        $this->approvedReview = Review::create([
            'reviewer_id' => $this->mentee->id,
            'reviewer_type' => get_class($this->mentee),
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
            'rating' => 4.0,
            'approved_at' => now(),
        ]);
        $this->unapprovedReview = Review::create([
            'reviewer_id' => $otherMentee->id,
            'reviewer_type' => get_class($otherMentee),
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
            'rating' => 4.0,
            'approved_at' => null,
        ]);
    }

    public function test_scopeWithUnapproved_includes_unapproved_reviews()
    {
        $approvedReviewIds = Review::pluck('id');
        $allReviewIds = Review::withUnapproved()->pluck('id');

        $this->assertFalse($approvedReviewIds->contains($this->unapprovedReview->id));
        $this->assertTrue($allReviewIds->contains($this->unapprovedReview->id));
    }

    public function test_isApproved_returns_false_when_approved_at_is_null()
    {
        $this->assertFalse($this->unapprovedReview->isApproved());
    }

    public function test_isApproved_returns_true_when_approved_at_is_set()
    {
        $this->assertTrue($this->approvedReview->isApproved());
    }

    public function test_approve_sets_approved_at_when_not_already_approved()
    {
        $this->unapprovedReview->approve();

        $this->assertNotNull($this->unapprovedReview->approved_at);
        $this->assertInstanceOf(Carbon::class, $this->unapprovedReview->approved_at);
    }

    public function test_unapprove_sets_approved_at_to_null_when_already_approved()
    {
        $this->approvedReview->unapprove();

        $this->assertNull($this->unapprovedReview->approved_at);
    }

    public function test_reviewer_returns_correct_reviewer()
    {
        $reviewer = $this->approvedReview->reviewer;

        $this->assertInstanceOf(Mentee::class, $reviewer);
        $this->assertEquals($this->mentee->id, $reviewer->id);
    }

    public function test_reviewable_returns_correct_reviewable()
    {
        $reviewable = $this->approvedReview->reviewable;

        $this->assertInstanceOf(Mentor::class, $reviewable);
        $this->assertEquals($this->mentor->id, $reviewable->id);
    }
}
