<?php

namespace Database\Factories;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->word . ' API Key',
            'provider' => $this->faker->randomElement(['google', 'openai', 'anthropic']),
            'key' => 'sk-' . $this->faker->sha256,
        ];
    }
}