<?php

namespace Fajarwz\LaravelReview\Exceptions;

use Exception;

class ReviewNotFoundException extends Exception
{
    public function __construct($message = 'Review not found')
    {
        parent::__construct($message);
    }
}
