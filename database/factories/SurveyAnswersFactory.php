<?php

namespace Database\Factories;

use App\Models\SurveyAnswers;
use App\Models\Surveys;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyAnswersFactory extends Factory
{
    protected $model = SurveyAnswers::class;

    public function definition(): array
    {
        return [
            'survey_id' => Surveys::factory(),
            'user_id' => Users::factory(),
            'answer' => fake()->numberBetween(0, 2),
        ];
    }
}