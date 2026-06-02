<?php

namespace Database\Factories;

use App\Constants\Master\Models\WorkerDocument\WorkerDocumentStatusConst;
use App\Models\WorkerDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkerDocumentFactory extends Factory
{
    protected $model = WorkerDocument::class;

    public function definition(): array
    {
        return [
            'worker_profile_id' => null, // Unlinked by default
            'type' => 'cccd',
            'file_url' => 'documents/'.$this->faker->uuid().'.jpg',
            'status' => WorkerDocumentStatusConst::PENDING,
            'verified_at' => null,
            'created_by' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkerDocumentStatusConst::APPROVED,
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkerDocumentStatusConst::REJECTED,
        ]);
    }
}
