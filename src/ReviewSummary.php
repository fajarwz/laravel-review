<?php

namespace Fajarwz\LaravelReview;

use Illuminate\Database\Eloquent\Model;

class ReviewSummary extends Model
{
    protected $fillable = [
        'average_rating',
        'review_count',
    ];

    public function reviewable()
    {
        return $this->morphTo();
    }
}
