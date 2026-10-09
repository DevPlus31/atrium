<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use Modules\Users\Infrastructure\Models\Invitation;

final class AcceptInvitationRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * The invitation must still be open and its address still free.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $invitation = $this->invitation();

                if (! $invitation->isPending()) {
                    $validator->errors()->add('invitation', __('This invitation is no longer valid.'));
                } elseif (User::query()->where('email', $invitation->email)->exists()) {
                    $validator->errors()->add('invitation', __('An account with this email already exists. Sign in instead.'));
                }
            },
        ];
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        assert($invitation instanceof Invitation);

        return $invitation;
    }
}
