<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('announcement.message');
        $this->migrator->add('announcement.level', 'info');
        $this->migrator->add('announcement.ends_at');
    }
};
