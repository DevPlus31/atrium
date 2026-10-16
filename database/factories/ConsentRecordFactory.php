<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ConsentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConsentRecord>
 */
final class ConsentRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_id' => (string) Str::uuid(),
            'user_id' => null,
            'version' => Config::string('privacy.consent.version'),
            'granted' => ['analytics'],
            'gpc' => false,
            'created_at' => now(),
        ];
    }
}
