<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleCode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

#[Description('Scaffold a full CRUD DDD slice (aggregate) inside an existing module')]
#[Signature('make:aggregate {module : The module name (e.g. Catalog)} {aggregate : The aggregate name (e.g. Product)}')]
final class MakeAggregateCommand extends Command
{
    use GeneratesModuleCode;

    public function handle(Filesystem $files): int
    {
        $module = $this->studlyArgument('module');
        $aggregate = $this->studlyArgument('aggregate');

        if ($module === null || $aggregate === null) {
            return self::FAILURE;
        }

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

        $providerPath = $modulePath.'/Providers/'.$module.'ServiceProvider.php';

        if (! str_contains($files->get($providerPath), '// @ddd-bindings')) {
            $this->components->error(sprintf(
                'Module [%s] already has an aggregate. make:aggregate scaffolds one aggregate per module; add further aggregates by hand or create a new module.',
                $module,
            ));

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

        $this->writeAggregateFiles($files, $modulePath, $aggregate, $plural, $slug, $replacements);
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
    private function writeAggregateFiles(Filesystem $files, string $modulePath, string $aggregate, string $plural, string $slug, array $replacements): void
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
            'aggregate/form-fields' => $modulePath.'/resources/js/components/'.$slug.'-form-fields.tsx',
            'aggregate/test-controller' => $modulePath.'/tests/Feature/'.$aggregate.'ControllerTest.php',
            'aggregate/test-actions' => $modulePath.'/tests/Unit/Actions/'.$aggregate.'ActionsTest.php',
            'aggregate/test-policy' => $modulePath.'/tests/Unit/Policies/'.$aggregate.'PolicyTest.php',
            'aggregate/test-query' => $modulePath.'/tests/Unit/Queries/'.$plural.'IndexQueryTest.php',
        ];

        foreach ($map as $stub => $target) {
            $files->ensureDirectoryExists(dirname($target));
            $files->put($target, $this->render($files, $stub, $replacements));
        }

        $this->registerTranslations($files, $modulePath, array_values($map));
    }

    /**
     * Add the strings the generated code translates (`t()`, `tChoice()`,
     * `__()`) to the module's own lang/en.json — unless the shell catalogue
     * already has them — so translators find them and the module takes them
     * along wherever it goes.
     *
     * @param  list<string>  $paths
     */
    private function registerTranslations(Filesystem $files, string $modulePath, array $paths): void
    {
        $shell = $this->catalogue($files, base_path('lang/en.json'));
        $catalogPath = $modulePath.'/lang/en.json';
        $module = $this->catalogue($files, $catalogPath);

        foreach ($paths as $path) {
            preg_match_all('/\b(?:t|tChoice|__)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', $files->get($path), $matches);

            foreach ($matches[2] as $key) {
                $key = stripslashes($key);

                if (! array_key_exists($key, $shell)) {
                    $module[$key] ??= $key;
                }
            }
        }

        $this->writeCatalogue($files, $catalogPath, $module);

        // Every other available locale gets the new strings too, in English
        // until someone translates them, so each catalogue keeps every key.
        foreach (array_keys(Config::array('app.available_locales')) as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $path = sprintf('%s/lang/%s.json', $modulePath, $locale);
            $translated = $this->catalogue($files, $path);
            $untranslated = array_diff_key($module, $translated);

            $this->writeCatalogue($files, $path, [...$translated, ...$untranslated]);

            if ($untranslated !== []) {
                $this->components->warn(sprintf('Translate %d new string(s) in %s.', count($untranslated), Str::after($path, base_path().'/')));
            }
        }
    }

    /**
     * @param  array<string, string>  $strings
     */
    private function writeCatalogue(Filesystem $files, string $path, array $strings): void
    {
        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, json_encode($strings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
    }

    /**
     * @return array<string, string>
     */
    private function catalogue(Filesystem $files, string $path): array
    {
        if (! $files->exists($path)) {
            return [];
        }

        /** @var array<string, string> $strings */
        $strings = json_decode($files->get($path), true, flags: JSON_THROW_ON_ERROR);

        return $strings;
    }

    private function wireProvider(Filesystem $files, string $modulePath, string $module, string $aggregate, string $slug, string $label): void
    {
        $path = $modulePath.'/Providers/'.$module.'ServiceProvider.php';

        // The policy needs no registration: Laravel's policy discovery finds
        // Modules\<Module>\Policies\<Aggregate>Policy for the model.
        $imports = implode(PHP_EOL, [
            'use App\\Modules\\PermissionRegistry;',
            sprintf('use Modules\\%s\\Domain\\Repositories\\%sRepository;', $module, $aggregate),
            sprintf('use Modules\\%s\\Infrastructure\\Repositories\\Eloquent%sRepository;', $module, $aggregate),
        ]);

        $bindings = sprintf('        $this->app->bind(%sRepository::class, Eloquent%sRepository::class);', $aggregate, $aggregate);

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
}
