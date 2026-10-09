<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncPermissions;
use App\Modules\PermissionRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Sync module-declared permissions and default role assignments to the database')]
#[Signature('admin:sync-permissions')]
final class SyncPermissionsCommand extends Command
{
    public function handle(PermissionRegistry $registry, SyncPermissions $sync): int
    {
        $count = $sync->handle($registry);

        $this->components->info(sprintf('Synced %d permissions.', $count));

        return self::SUCCESS;
    }
}
