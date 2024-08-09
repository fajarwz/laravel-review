<?php

namespace Fajarwz\LaravelReview;

use Illuminate\Support\ServiceProvider;

class LaravelReviewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services
     *
     * @return void
     */
    public function boot(): void
    {
        /**
         * Publish the database migration files
         */
        $this->publishes([
            __DIR__.'\\..\\database\\migrations' => base_path('database/migrations'),
        ], 'laravel-review_migrations');
    }
}
