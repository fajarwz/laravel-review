<?php

namespace Fajarwz\LaravelReview\Scopes;

use Illuminate\Database\Eloquent\Builder;

class ApprovedReviewsScope
{
    /**
     * Applies a scope to only include approved reviews.
     * 
     * This scope adds a `whereNotNull('approved_at')` condition to the query.
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function apply(Builder $builder): void
    {
        $builder->whereNotNull('approved_at');
    }
}