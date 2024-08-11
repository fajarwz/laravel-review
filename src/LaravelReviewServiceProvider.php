<?php

namespace Fajarwz\LaravelReview;

use Illuminate\Support\ServiceProvider;

class LaravelReviewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services
     */
    public function boot(): void
    {
        /**
         * Publish the database migration files
         */
        $this->publishes([
            __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations' => base_path('database/migrations'),
        ], 'laravel-review_migrations');
    }
}
