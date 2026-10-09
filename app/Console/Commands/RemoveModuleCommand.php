<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

#[Description('Remove a module: its folder (code, pages, translations, tests) and its provider registration')]
#[Signature('module:remove {name : The module name in StudlyCase (e.g. Shop)} {--force : Skip the confirmation}')]
final class RemoveModuleCommand extends Command
{
    public function handle(Filesystem $files): int
    {
        /** @var string $name */
        $name = $this->argument('name');

        $studly = Str::studly($name);
        $modulePath = base_path('app-modules/'.$studly);

        if (! $files->isDirectory($modulePath)) {
            $this->components->error(sprintf('Module [%s] does not exist.', $studly));

            return self::FAILURE;
        }

        $references = $this->referencesFromElsewhere($studly);

        if ($references !== []) {
            $this->components->warn(sprintf('These files still mention Modules\\%s and need attention after removal:', $studly));
            $this->components->bulletList($references);
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('Delete app-modules/%s and unregister its provider?', $studly))) {
            $this->components->info('Nothing removed.');

            return self::SUCCESS;
        }

        $tables = $this->createdTables($files, $modulePath);

        $files->deleteDirectory($modulePath);
        $this->unregisterProvider($files, $studly);

        $this->components->info(sprintf('Module [%s] removed.', $studly));
        $this->components->bulletList([
            "php artisan admin:sync-permissions (prunes the module's permissions)",
            $tables === []
                ? 'The module created no tables.'
                : sprintf('Drop its tables with a new migration when you no longer need the data: %s', implode(', ', $tables)),
            'php artisan typescript:transform && php artisan wayfinder:generate --with-form',
            'bun run build',
        ]);

        return self::SUCCESS;
    }

    /**
     * Shell and other-module files that mention the module's namespace — a
     * listener keyed by its event class name, say — relative to the base path.
     *
     * @return list<string>
     */
    private function referencesFromElsewhere(string $studly): array
    {
        $needle = 'Modules\\'.$studly.'\\';

        $finder = Finder::create()
            ->files()
            ->in(array_filter([base_path('app'), base_path('app-modules'), base_path('config'), base_path('routes')], is_dir(...)))
            ->exclude($studly)
            ->name('*.php')
            ->contains($needle);

        $references = [];

        foreach ($finder as $file) {
            $references[] = Str::after($file->getPathname(), base_path().DIRECTORY_SEPARATOR);
        }

        sort($references);

        return $references;
    }

    /**
     * The tables the module's migrations create, so the next step can name
     * them. The data is left in place: dropping it is a deliberate migration.
     *
     * @return list<string>
     */
    private function createdTables(Filesystem $files, string $modulePath): array
    {
        $tables = [];

        foreach (glob($modulePath.'/Database/Migrations/*.php') ?: [] as $migration) {
            preg_match_all("/Schema::create\\(\\s*'([^']+)'/", $files->get($migration), $matches);

            array_push($tables, ...$matches[1]);
        }

        return $tables;
    }

    private function unregisterProvider(Filesystem $files, string $studly): void
    {
        $path = base_path('bootstrap/providers.php');
        $entry = sprintf('    Modules\\%s\\Providers\\%sServiceProvider::class,', $studly, $studly);

        $files->put($path, str_replace($entry.PHP_EOL, '', $files->get($path)));
    }
}
