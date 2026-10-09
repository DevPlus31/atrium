<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

#[Description('Scaffold a new DDD module (bounded context) under app-modules')]
#[Signature('make:module {name : The module name in StudlyCase (e.g. Catalog)}')]
final class MakeModuleCommand extends Command
{
    /**
     * Empty layer directories seeded with a .gitkeep so the structure is tracked.
     *
     * @var list<string>
     */
    private const array LAYER_DIRECTORIES = [
        'Domain/ValueObjects',
        'Domain/Events',
        'Domain/Exceptions',
        'Domain/Repositories',
        'Infrastructure/Models',
        'Infrastructure/Repositories',
        'Database/Migrations',
        'resources/js/pages',
    ];

    public function handle(Filesystem $files): int
    {
        /** @var string $name */
        $name = $this->argument('name');

        $studly = Str::studly($name);
        $kebab = Str::kebab($studly);
        $modulePath = base_path('app-modules/'.$studly);

        if ($files->isDirectory($modulePath)) {
            $this->components->error(sprintf('Module [%s] already exists.', $studly));

            return self::FAILURE;
        }

        foreach (self::LAYER_DIRECTORIES as $directory) {
            $files->ensureDirectoryExists($modulePath.'/'.$directory);
            $files->put($modulePath.'/'.$directory.'/.gitkeep', '');
        }

        $replacements = ['{{ studly }}' => $studly, '{{ kebab }}' => $kebab];

        $files->ensureDirectoryExists($modulePath.'/Providers');
        $files->put(
            $modulePath.'/Providers/'.$studly.'ServiceProvider.php',
            $this->render($files, 'module.provider', $replacements),
        );

        $files->ensureDirectoryExists($modulePath.'/routes');
        $files->put(
            $modulePath.'/routes/admin.php',
            $this->render($files, 'module.routes', $replacements),
        );

        $testPath = $modulePath.'/tests/Feature';
        $files->ensureDirectoryExists($testPath);
        $files->put(
            $testPath.'/'.$studly.'ModuleTest.php',
            $this->render($files, 'module.test', $replacements),
        );

        $this->registerProvider($files, $studly);

        $this->components->info(sprintf('Module [%s] created.', $studly));
        $this->components->bulletList([
            sprintf('Scaffold an aggregate: php artisan make:aggregate %s <Aggregate>', $studly),
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function render(Filesystem $files, string $stub, array $replacements): string
    {
        $contents = $files->get(base_path('stubs/ddd/'.$stub.'.stub'));

        return str_replace(array_keys($replacements), array_values($replacements), $contents);
    }

    private function registerProvider(Filesystem $files, string $studly): void
    {
        // The module is new (guarded by the directory check in handle), so the
        // entry is appended before the array's closing bracket without a guard.
        $path = base_path('bootstrap/providers.php');
        $entry = sprintf('    Modules\\%s\\Providers\\%sServiceProvider::class,', $studly, $studly);

        $files->put($path, (string) preg_replace('/(\n];)/', PHP_EOL.$entry.'$1', $files->get($path), 1));
    }
}
