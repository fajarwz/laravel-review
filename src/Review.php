<?php

namespace Fajarwz\LaravelReview;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'reviewer_id',
        'reviewer_type',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'content',
        'approved_at',
    ];

    public function reviewer()
    {
        return $this->morphTo();
    }

    public function reviewable()
    {
        return $this->morphTo();
    }
}
