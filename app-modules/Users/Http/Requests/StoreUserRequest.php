<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Models\User;
use App\Rules\AccountRules;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Http\Requests\Concerns\ReadsAccountInput;

final class StoreUserRequest extends FormRequest
{
    use ReadsAccountInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $actor = $this->user();

        assert($actor instanceof User);

        return [
            'name' => AccountRules::name(),
            'email' => AccountRules::email(),
            'password' => AccountRules::password(),
            ...AccountRules::roles($actor),
        ];
    }
}
