<?php

namespace Fajarwz\LaravelReview\Tests;

use Fajarwz\LaravelReview\LaravelReviewServiceProvider;
use Fajarwz\LaravelReview\Tests\Models\Product;
use Fajarwz\LaravelReview\Tests\Models\User;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $product;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ .DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR .'Migrations',);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->loadMigrationsFrom(__DIR__ .DIRECTORY_SEPARATOR .'..'.DIRECTORY_SEPARATOR .'database'.DIRECTORY_SEPARATOR .'migrations');
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->product = Product::factory()->create();
        $this->user = User::factory()->create();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelReviewServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
