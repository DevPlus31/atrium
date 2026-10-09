<?php

declare(strict_types=1);

namespace Modules\Api\Http\Requests;

use App\Models\User;
use App\Modules\PermissionRegistry;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateApiTokenRequest extends FormRequest
{
    /**
     * How long a token may live, in days. Every token expires.
     *
     * @var list<int>
     */
    public const array LIFETIMES = [30, 90, 365];

    /**
     * The permissions a user may put on a token: the ones they hold.
     *
     * @return list<string>
     */
    public static function grantableAbilities(User $user): array
    {
        return array_values(array_filter(
            resolve(PermissionRegistry::class)->permissions(),
            $user->can(...),
        ));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expires_in_days' => ['required', 'integer', Rule::in(self::LIFETIMES)],
            'abilities' => ['array'],
            'abilities.*' => ['string', 'distinct', Rule::in(self::grantableAbilities($this->actor()))],
        ];
    }

    /**
     * @return list<string>
     */
    public function abilities(): array
    {
        /** @var list<string> $abilities */
        $abilities = $this->validated('abilities', []);

        return $abilities;
    }

    public function expiresAt(): CarbonInterface
    {
        return now()->addDays($this->integer('expires_in_days'));
    }

    private function actor(): User
    {
        $user = $this->user();

        assert($user instanceof User);

        return $user;
    }
}
