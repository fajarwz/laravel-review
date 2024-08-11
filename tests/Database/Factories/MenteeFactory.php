<?php

namespace Fajarwz\LaravelReview\Tests\Database\Factories;

use Fajarwz\LaravelReview\Tests\Models\Mentee;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenteeFactory extends Factory
{
    protected $model = Mentee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
