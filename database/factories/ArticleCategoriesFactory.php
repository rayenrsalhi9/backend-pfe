<?php

namespace Database\Factories;

use App\Models\ArticleCategories;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleCategoriesFactory extends Factory
{
    protected $model = ArticleCategories::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'description' => fake()->sentence(),
        ];
    }
}
