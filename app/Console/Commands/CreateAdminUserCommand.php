<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\ValidEmail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Users\Actions\CreateUser;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Description('Create a verified admin user (first-run bootstrap)')]
#[Signature('admin:create-user {--name=} {--email=} {--password=}')]
final class CreateAdminUserCommand extends Command
{
    public function handle(CreateUser $createUser): int
    {
        $name = $this->stringOption('name') ?? text(label: 'Name', required: true);
        $email = $this->stringOption('email') ?? text(label: 'Email address', required: true);
        $password = $this->stringOption('password') ?? password(label: 'Password', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'max:255',
                    'email',
                    new ValidEmail,
                    Rule::unique(User::class),
                ],
                'password' => ['required', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->call('admin:sync-permissions');

        $user = $createUser->handle(
            name: $name,
            email: $email,
            password: $password,
            roles: ['admin'],
            verified: true,
        );

        $this->components->info(sprintf('Admin user %s created.', $user->email));

        return self::SUCCESS;
    }

    private function stringOption(string $key): ?string
    {
        $value = $this->option($key);

        return is_string($value) ? $value : null;
    }
}
