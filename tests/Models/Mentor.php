<?php

namespace Fajarwz\LaravelReview\Tests\Models;

use Fajarwz\LaravelReview\Traits\CanBeReviewed;
use Fajarwz\LaravelReview\Traits\CanReview;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    use CanBeReviewed, CanReview;
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Fajarwz\LaravelReview\Tests\Database\Factories\MentorFactory::new();
    }
}
