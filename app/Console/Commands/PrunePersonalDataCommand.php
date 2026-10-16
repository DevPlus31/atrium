<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\PruneExpiredPersonalData;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Delete personal data past its retention period (config/privacy.php)')]
#[Signature('privacy:prune')]
final class PrunePersonalDataCommand extends Command
{
    public function handle(PruneExpiredPersonalData $prune): int
    {
        foreach ($prune() as $kind => $count) {
            $this->components->twoColumnDetail(str_replace('_', ' ', $kind), (string) $count);
        }

        return self::SUCCESS;
    }
}
