<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('business.timezone', 'UTC');
        $this->migrator->add('business.locale', 'en-US');
        $this->migrator->add('business.currency', 'USD');
        $this->migrator->add('business.distance_unit', 'km');
    }
};
