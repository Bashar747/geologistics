<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('96793456732'),
            'role' => 'customer',
            'email_verified_at' => now(),
            'password' => static::$password ??= bcrypt('password123'),
            'remember_token' => Str::random(10),
        ];
    }

    // حالة خاصة: مستخدم بدور سائق
    public function driver(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'driver',
        ]);
    }


    public function dispatcher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'dispatcher',
        ]);
    }

    // حالة خاصة: مستخدم أدمن
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}