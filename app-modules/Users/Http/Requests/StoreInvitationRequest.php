<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Models\User;
use App\Rules\GrantableRole;
use App\Rules\ValidEmail;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Users\Http\Requests\Concerns\ReadsAccountInput;
use Modules\Users\Infrastructure\Models\Invitation;
use Spatie\Permission\Models\Role;

final class StoreInvitationRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                new ValidEmail,
                Rule::unique(User::class),
                Rule::unique(Invitation::class)->where(fn (Builder $query): Builder => $query
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())),
            ],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(Role::class, 'name'), new GrantableRole($actor)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('This person already has an account or a pending invitation.'),
        ];
    }
}
