<?php

namespace Database\Factories;

use App\Models\Surveys;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveysFactory extends Factory
{
    protected $model = Surveys::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'type' => fake()->randomElement(['simple', 'rating', 'satisfaction']),
            'privacy' => 'public',
            'created_by' => Users::factory(),
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'blog' => true,
            'forum' => true,
            'closed' => false,
            'users' => null,
        ];
    }

    public function private(): static
    {
        return $this->state(fn(array $attributes) => [
            'privacy' => 'private',
        ])->afterMaking(function (Surveys $survey) {
            if ($survey->created_by) {
                $users = $survey->users ?? [];
                if (!is_array($users)) {
                    $users = [];
                }
                if (!in_array($survey->created_by, $users)) {
                    $users[] = $survey->created_by;
                }
                $survey->users = $users;
            }
        });
    }

    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'closed' => true,
        ]);
    }
}