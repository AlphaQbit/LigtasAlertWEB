<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'facility' => fake()->randomElement(['Main Hospital', 'North Annex', 'Clinic A']),
            'type' => fake()->randomElement(['Cardiac Arrest', 'Fall', 'Fire Alarm', 'Security Breach']),
            'location' => 'Room '.fake()->numberBetween(1, 300),
            'details' => fake()->sentence(),
            'status' => Alert::STATUS_ACTIVE,
            'acknowledged_at' => null,
            'resolved_at' => null,
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Alert::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now()->subMinutes(fake()->numberBetween(1, 10)),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Alert::STATUS_RESOLVED,
            'acknowledged_at' => now()->subMinutes(15),
            'resolved_at' => now()->subMinutes(fake()->numberBetween(1, 14)),
        ]);
    }
}
