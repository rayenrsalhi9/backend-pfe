<?php

namespace Database\Factories;

use App\Models\Forums;
use App\Models\ForumCategories;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumsFactory extends Factory
{
    protected $model = Forums::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'privacy' => fake()->randomElement(['public', 'private']),
            'created_by' => Users::factory(),
            'category_id' => ForumCategories::factory(),
            'closed' => false,
            'sub_category_id' => null,
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
