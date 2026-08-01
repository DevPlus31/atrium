<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

#[Description('Scaffold a full CRUD DDD slice (aggregate) inside an existing module')]
#[Signature('make:aggregate {module : The module name (e.g. Catalog)} {aggregate : The aggregate name (e.g. Product)}')]
final class MakeAggregateCommand extends Command
{
    public function handle(Filesystem $files): int
    {
        $module = Str::studly($this->stringArgument('module'));
        $aggregate = Str::studly($this->stringArgument('aggregate'));
        $modulePath = base_path('app-modules/'.$module);

        if (! $files->isDirectory($modulePath)) {
            $this->components->error(sprintf('Module [%s] does not exist. Run: php artisan make:module %s', $module, $module));

            return self::FAILURE;
        }

        $modelPath = $modulePath.'/Infrastructure/Models/'.$aggregate.'.php';

        if ($files->exists($modelPath)) {
            $this->components->error(sprintf('Aggregate [%s] already exists in module [%s].', $aggregate, $module));

            return self::FAILURE;
        }

        $plural = Str::pluralStudly($aggregate);
        $slug = Str::kebab($plural);

        $replacements = [
            '{{ module }}' => $module,
            '{{ moduleLower }}' => Str::lower($module),
            '{{ aggregate }}' => $aggregate,
            '{{ plural }}' => $plural,
            '{{ variable }}' => Str::camel($aggregate),
            '{{ variablePlural }}' => Str::camel($plural),
            '{{ table }}' => Str::snake($plural),
            '{{ slug }}' => $slug,
            '{{ label }}' => Str::headline($plural),
        ];

        $this->writeAggregateFiles($files, $modulePath, $module, $aggregate, $plural, $slug, $replacements);
        $this->wireProvider($files, $modulePath, $module, $aggregate, $slug, $replacements['{{ label }}']);

        $this->components->info(sprintf('Aggregate [%s] scaffolded in module [%s].', $aggregate, $module));
        $this->components->bulletList([
            'php artisan migrate',
            'php artisan admin:sync-permissions',
            'php artisan typescript:transform',
            'php artisan wayfinder:generate --with-form',
            'composer lint && bun run lint',
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function writeAggregateFiles(Filesystem $files, string $modulePath, string $module, string $aggregate, string $plural, string $slug, array $replacements): void
    {
        $timestamp = now()->format('Y_m_d_His');

        $map = [
            'aggregate/repository' => $modulePath.'/Domain/Repositories/'.$aggregate.'Repository.php',
            'aggregate/model' => $modulePath.'/Infrastructure/Models/'.$aggregate.'.php',
            'aggregate/eloquent-repository' => $modulePath.'/Infrastructure/Repositories/Eloquent'.$aggregate.'Repository.php',
            'aggregate/migration' => $modulePath.'/Database/Migrations/'.$timestamp.'_create_'.$replacements['{{ table }}'].'_table.php',
            'aggregate/factory' => $modulePath.'/Database/Factories/'.$aggregate.'Factory.php',
            'aggregate/action-create' => $modulePath.'/Actions/Create'.$aggregate.'.php',
            'aggregate/action-update' => $modulePath.'/Actions/Update'.$aggregate.'.php',
            'aggregate/action-delete' => $modulePath.'/Actions/Delete'.$aggregate.'.php',
            'aggregate/query' => $modulePath.'/Queries/'.$plural.'IndexQuery.php',
            'aggregate/data' => $modulePath.'/Data/'.$aggregate.'Data.php',
            'aggregate/controller' => $modulePath.'/Http/Controllers/'.$aggregate.'Controller.php',
            'aggregate/request-store' => $modulePath.'/Http/Requests/Store'.$aggregate.'Request.php',
            'aggregate/request-update' => $modulePath.'/Http/Requests/Update'.$aggregate.'Request.php',
            'aggregate/policy' => $modulePath.'/Policies/'.$aggregate.'Policy.php',
            'aggregate/routes' => $modulePath.'/routes/admin.php',
            'aggregate/page-index' => $modulePath.'/resources/js/pages/index.tsx',
            'aggregate/page-create' => $modulePath.'/resources/js/pages/create.tsx',
            'aggregate/page-edit' => $modulePath.'/resources/js/pages/edit.tsx',
            'aggregate/columns' => $modulePath.'/resources/js/components/'.$slug.'-columns.tsx',
            'aggregate/test-controller' => base_path('tests/Feature/Modules/'.$module.'/'.$aggregate.'ControllerTest.php'),
        ];

        foreach ($map as $stub => $target) {
            $files->ensureDirectoryExists(dirname($target));
            $files->put($target, $this->render($files, $stub, $replacements));
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function render(Filesystem $files, string $stub, array $replacements): string
    {
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $files->get(base_path('stubs/ddd/'.$stub.'.stub')),
        );
    }

    private function wireProvider(Filesystem $files, string $modulePath, string $module, string $aggregate, string $slug, string $label): void
    {
        $path = $modulePath.'/Providers/'.$module.'ServiceProvider.php';

        $imports = implode(PHP_EOL, [
            'use App\\Modules\\PermissionRegistry;',
            'use Illuminate\\Support\\Facades\\Gate;',
            sprintf('use Modules\\%s\\Domain\\Repositories\\%sRepository;', $module, $aggregate),
            sprintf('use Modules\\%s\\Infrastructure\\Models\\%s;', $module, $aggregate),
            sprintf('use Modules\\%s\\Infrastructure\\Repositories\\Eloquent%sRepository;', $module, $aggregate),
            sprintf('use Modules\\%s\\Policies\\%sPolicy;', $module, $aggregate),
        ]);

        $bindings = implode(PHP_EOL, [
            sprintf('        Gate::policy(%s::class, %sPolicy::class);', $aggregate, $aggregate),
            '',
            sprintf('        $this->app->bind(%sRepository::class, Eloquent%sRepository::class);', $aggregate, $aggregate),
        ]);

        $navigation = implode(PHP_EOL, [
            '        $nav->add(',
            '            module: $this->name(),',
            sprintf("            label: '%s',", $label),
            sprintf("            routeName: 'admin.%s.index',", $slug),
            "            icon: 'box',",
            sprintf("            permission: '%s.view',", $slug),
            sprintf("            group: '%s',", $module),
            '            sort: 10,',
            '        );',
        ]);

        $permissions = implode(PHP_EOL, array_map(
            static fn (string $ability): string => sprintf("        \$permissions->declare('%s.%s', roles: ['admin']);", $slug, $ability),
            ['view', 'create', 'update', 'delete'],
        ));

        $contents = str_replace(
            ['use App\\Modules\\PermissionRegistry;', '        // @ddd-bindings', '        // @ddd-navigation', '        // @ddd-permissions'],
            [$imports, $bindings, $navigation, $permissions],
            $files->get($path),
        );

        $files->put($path, $contents);
    }

    private function stringArgument(string $key): string
    {
        /** @var string $value */
        $value = $this->argument($key);

        return $value;
    }
}
