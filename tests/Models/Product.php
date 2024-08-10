<?php

namespace Fajarwz\LaravelReview\Tests\Models;

use Fajarwz\LaravelReview\Traits\CanBeReviewed;
use Fajarwz\LaravelReview\Traits\HasPackageFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasPackageFactory;
    use CanBeReviewed;

    public $timestamps = false;

    protected $guarded = [];
}
