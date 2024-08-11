<?php

namespace Fajarwz\LaravelReview\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ApprovedReviewsScope implements Scope
{
    /**
     * Applies a scope to only include approved reviews.
     *
     * This scope adds a `whereNotNull('approved_at')` condition to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotNull('approved_at');
    }
}
