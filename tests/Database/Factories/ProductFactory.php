<?php

namespace Fajarwz\LaravelReview\Tests\Database\Factories;

use Fajarwz\LaravelReview\Tests\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(),
        ];
    }
}
