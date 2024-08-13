<?php

namespace Fajarwz\LaravelReview\Tests;

use Fajarwz\LaravelReview\LaravelReviewServiceProvider;
use Fajarwz\LaravelReview\Tests\Models\Mentee;
use Fajarwz\LaravelReview\Tests\Models\Mentor;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $mentor;

    protected $mentee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
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
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function setUpDatabase()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $migration = require __DIR__.'/../database/migrations/create_reviews_table.php.stub';
        $migration->up();

        $this->mentor = Mentor::factory()->create();
        $this->mentee = Mentee::factory()->create();
    }
}
