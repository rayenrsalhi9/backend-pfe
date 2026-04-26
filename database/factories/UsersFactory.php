<?php

namespace Database\Factories;

use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UsersFactory extends Factory
{
    protected $model = Users::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'firstName' => fake()->firstName(),
            'lastName' => fake()->lastName(),
            'userName' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'emailConfirmed' => true,
            'password' => static::$password ??= Hash::make('password'),
            'isDeleted' => false,
            'phoneNumber' => fake()->phoneNumber(),
            'phoneNumberConfirmed' => true,
            'twoFactorEnabled' => false,
            'lockoutEnabled' => false,
            'accessFailedCount' => 0,
            'direction' => fake()->randomElement(['North', 'South', 'East', 'West']),
        ];
    }
}