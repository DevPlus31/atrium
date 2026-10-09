<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Settings\GeneralSettings;
use Illuminate\View\View;

/**
 * Hands every email (resources/views/vendor/mail) the app's branding: the
 * uploaded logo and the support address from Settings → General.
 */
final readonly class MailBrandingComposer
{
    public function __construct(private GeneralSettings $settings)
    {
        //
    }

    public function compose(View $view): void
    {
        $view->with([
            'logoUrl' => $this->settings->logoUrl(),
            'supportEmail' => $this->settings->support_email,
        ]);
    }
}
