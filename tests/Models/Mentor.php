<?php

namespace Fajarwz\LaravelReview\Tests\Models;

use Fajarwz\LaravelReview\Traits\CanBeReviewed;
use Fajarwz\LaravelReview\Traits\CanReview;
use Fajarwz\LaravelReview\Traits\HasPackageFactory;
use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    use CanBeReviewed, CanReview;
    use HasPackageFactory;

    public $timestamps = false;

    protected $guarded = [];
}
