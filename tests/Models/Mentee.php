<?php

namespace Fajarwz\LaravelReview\Tests\Models;

use Fajarwz\LaravelReview\Traits\CanBeReviewed;
use Fajarwz\LaravelReview\Traits\CanReview;
use Fajarwz\LaravelReview\Traits\HasPackageFactory;
use Illuminate\Database\Eloquent\Model;

class Mentee extends Model
{
    use HasPackageFactory;
    use CanReview, CanBeReviewed;

    public $timestamps = false;

    protected $guarded = [];
}
