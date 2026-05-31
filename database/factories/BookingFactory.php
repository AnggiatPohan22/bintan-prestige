<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_code' =>
                'BOOK-' . strtoupper(fake()->bothify('###??')),

            'customer_name' => fake()->name(),

            'customer_email' => fake()->safeEmail(),

            'customer_phone' => fake()->phoneNumber(),

            'travel_date' =>
                fake()->dateTimeBetween('now', '+30 days'),

            'notes' => fake()->sentence(),

            'status' => fake()->randomElement([
                'pending',
                'contacted',
                'confirmed'
            ]),
        ];
    }
}