<?php

namespace Database\Factories;

use App\Models\Vector;
use Illuminate\Database\Eloquent\Factories\Factory;

class VectorFactory extends Factory
{
    protected $model = Vector::class;

    public function definition(): array
    {
        $schemas = [
            'fattura' => [
                'Fornitore' => 'string',
                'Data' => 'date',
                'Totale' => 'float',
                'IVA' => 'float',
                'Numero Fattura' => 'string',
            ],
            'ricetta' => [
                'Farmaco' => 'string',
                'Dosaggio' => 'string',
                'Posologia' => 'string',
                'Durata' => 'integer',
                'Medico' => 'string',
            ],
            'contratto' => [
                'Parti' => 'string',
                'Data Inizio' => 'date',
                'Data Fine' => 'date',
                'Importo' => 'float',
                'Tipo' => 'string',
            ],
        ];

        $schemaName = $this->faker->randomElement(array_keys($schemas));

        return [
            'user_id' => \App\Models\User::factory(),
            'name' => ucfirst($schemaName) . ' ' . $this->faker->word,
            'format' => $this->faker->randomElement(['json', 'csv', 'xml']),
            'data' => $schemas[$schemaName],
        ];
    }
}