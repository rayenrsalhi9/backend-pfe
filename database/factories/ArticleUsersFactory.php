<?php

namespace Database\Factories;

use App\Models\ArticleUsers;
use App\Models\Articles;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleUsersFactory extends Factory
{
    protected $model = ArticleUsers::class;

    public function definition(): array
    {
        return [
            'article_id' => Articles::factory(),
            'user_id' => Users::factory(),
        ];
    }
}
