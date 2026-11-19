<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $mimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $mimeType = $this->faker->randomElement($mimeTypes);

        $extension = match($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'pdf',
        };

        return [
            'user_id' => \App\Models\User::factory(),
            'original_name' => $this->faker->word . '.' . $extension,
            'path' => 'documents/' . $this->faker->uuid . '.' . $extension,
            'mime_type' => $mimeType,
            'size_in_kb' => $this->faker->numberBetween(10, 5000),
            'content' => $this->faker->optional(0.3)->text(2000),
        ];
    }
}