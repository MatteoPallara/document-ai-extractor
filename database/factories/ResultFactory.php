<?php

namespace Database\Factories;

use App\Models\Result;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        $sampleData = [
            'Fornitore' => 'Mario Rossi Srl',
            'Data' => '2025-01-15',
            'Totale' => '1250.00',
            'IVA' => '220.00',
            'Numero Fattura' => 'FATT-2025-001',
        ];

        $statuses = ['pending', 'processing_ocr', 'processing_llm', 'completed', 'failed'];

        return [
            'document_id' => \App\Models\Document::factory(),
            'vector_id' => \App\Models\Vector::factory(),
            'data' => $sampleData,
            'ai_model' => $this->faker->randomElement(['gemini-2.5-flash', 'gpt-4o', 'llama3', 'mistral']),
            'status' => $this->faker->randomElement($statuses),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}