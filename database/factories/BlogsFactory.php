<?php

namespace Database\Factories;

use App\Models\Blogs;
use App\Models\BlogCategories;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogsFactory extends Factory
{
    protected $model = Blogs::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'subtitle' => fake()->sentence(6),
            'body' => fake()->paragraphs(3, true),
            'picture' => 'images/' . fake()->uuid() . '.jpg',
            'privacy' => fake()->randomElement(['public', 'private']),
            'created_by' => Users::factory(),
            'category_id' => BlogCategories::factory(),
            'banner' => false,
            'expiration' => false,
            'start_date' => null,
            'end_date' => null,
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
