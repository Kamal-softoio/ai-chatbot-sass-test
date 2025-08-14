<?php

namespace Database\Factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'company_name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'plan' => fake()->randomElement(['free', 'basic', 'premium']),
            'remember_token' => Str::random(10),
        ];
    }
}
