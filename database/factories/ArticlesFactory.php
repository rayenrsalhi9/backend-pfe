<?php

namespace Database\Factories;

use App\Models\Articles;
use App\Models\ArticleCategories;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticlesFactory extends Factory
{
    protected $model = Articles::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'short_text' => fake()->paragraph(),
            'long_text' => fake()->paragraphs(3, true),
            'picture' => 'images/' . fake()->uuid() . '.jpg',
            'privacy' => fake()->randomElement(['public', 'private']),
            'created_by' => Users::factory(),
            'article_category_id' => ArticleCategories::factory(),
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => ['privacy' => 'public']);
    }

    public function private(): static
    {
        return $this->state(fn () => ['privacy' => 'private']);
    }
}
