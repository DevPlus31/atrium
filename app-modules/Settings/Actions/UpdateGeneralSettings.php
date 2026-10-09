<?php

declare(strict_types=1);

namespace Modules\Settings\Actions;

use App\Settings\GeneralSettings;

final readonly class UpdateGeneralSettings
{
    public function handle(GeneralSettings $settings, ?string $supportEmail, bool $registrationOpen): void
    {
        $old = [
            'support_email' => $settings->support_email,
            'registration_open' => $settings->registration_open,
        ];

        $settings->support_email = $supportEmail;
        $settings->registration_open = $registrationOpen;
        $settings->save();

        activity('settings')
            ->event('updated')
            ->withProperties([
                'old' => $old,
                'attributes' => ['support_email' => $supportEmail, 'registration_open' => $registrationOpen],
            ])
            ->log('general-updated');
    }
}
