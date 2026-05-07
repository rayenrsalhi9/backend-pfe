<?php

namespace Database\Factories;

use App\Models\ForumCategories;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumCategoriesFactory extends Factory
{
    protected $model = ForumCategories::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'description' => fake()->sentence(),
        ];
    }
}
