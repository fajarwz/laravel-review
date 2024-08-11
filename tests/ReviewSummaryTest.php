<?php

namespace Fajarwz\LaravelReview\Tests;

use Fajarwz\LaravelReview\Models\Review;
use Fajarwz\LaravelReview\Tests\Models\Mentor;

class ReviewSummaryTest extends TestCase
{
    protected $approvedReview;

    protected $unapprovedReview;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approvedReview = Review::create([
            'reviewer_id' => $this->mentee->id,
            'reviewer_type' => get_class($this->mentee),
            'reviewable_id' => $this->mentor->id,
            'reviewable_type' => get_class($this->mentor),
            'rating' => 4.0,
            'approved_at' => now(),
        ]);
    }

    public function test_reviewable_returns_correct_reviewable()
    {
        $reviewable = $this->approvedReview->reviewable;

        $this->assertInstanceOf(Mentor::class, $reviewable);
        $this->assertEquals($this->mentor->id, $reviewable->id);
    }
}
