<?php

namespace Database\Factories;

use App\Models\ForumUsers;
use App\Models\Forums;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumUsersFactory extends Factory
{
    protected $model = ForumUsers::class;

    public function definition(): array
    {
        return [
            'forum_id' => Forums::factory(),
            'user_id' => Users::factory(),
        ];
    }
}
