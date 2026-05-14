<?php

namespace Database\Factories;

use App\Models\BlogUsers;
use App\Models\Blogs;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogUsersFactory extends Factory
{
    protected $model = BlogUsers::class;

    public function definition(): array
    {
        return [
            'blog_id' => Blogs::factory(),
            'user_id' => Users::factory(),
        ];
    }
}
