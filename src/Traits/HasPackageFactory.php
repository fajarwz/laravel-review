<?php

namespace Fajarwz\LaravelReview\Traits;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

trait HasPackageFactory
{
    use HasFactory;

    protected static function newFactory()
    {
        $modelName = Str::after(get_called_class(), 'Models\\');
        $path = DIRECTORY_SEPARATOR .'Fajarwz'.DIRECTORY_SEPARATOR .'LaravelReview'.DIRECTORY_SEPARATOR .'Tests'.DIRECTORY_SEPARATOR .'Database'.DIRECTORY_SEPARATOR.'Factories'.DIRECTORY_SEPARATOR.$modelName.'Factory';

        return $path::new();
    }
}
