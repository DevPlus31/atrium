<?php

declare(strict_types=1);

namespace Modules\Api\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'locale' => $this->preferredLocale() ?? Config::string('app.locale'),
            'timezone' => $this->timezone,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
