<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SentNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => fake()->randomElement(['sms', 'push', 'email']),
            'message' => fake()->randomElement([
                'شحنتك بالطريق إليك',
                'تم استلام طلبك بنجاح',
                'السائق وصل لنقطة الاستلام',
            ]),
            'status' => fake()->randomElement(['sent', 'failed', 'pending']),
            'sent_at' => fake()->optional(0.8)->dateTimeBetween('-1 month', 'now'),
        ];
    }
}