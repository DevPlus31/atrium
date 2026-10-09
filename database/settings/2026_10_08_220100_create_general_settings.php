<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.logo_path');
        $this->migrator->add('general.support_email');
        $this->migrator->add('general.registration_open', true);
    }
};
