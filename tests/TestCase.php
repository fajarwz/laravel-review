<?php

namespace Fajarwz\LaravelReview\Tests;

use Fajarwz\LaravelReview\LaravelReviewServiceProvider;
use Fajarwz\LaravelReview\Tests\Models\Mentor;
use Fajarwz\LaravelReview\Tests\Models\Mentee;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $mentor;
    protected $mentee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ .DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR .'Migrations',);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->loadMigrationsFrom(__DIR__ .DIRECTORY_SEPARATOR .'..'.DIRECTORY_SEPARATOR .'database'.DIRECTORY_SEPARATOR .'migrations');
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->mentor = Mentor::factory()->create();
        $this->mentee = Mentee::factory()->create();
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
