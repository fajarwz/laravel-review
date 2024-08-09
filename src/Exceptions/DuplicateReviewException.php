<?php

namespace Fajarwz\LaravelReview\Exceptions;

use Exception;

class DuplicateReviewException extends Exception
{
    public function __construct($message = 'Review already exists')
    {
        parent::__construct($message);
    }
}