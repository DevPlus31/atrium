<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Every `.php` file under `app-modules/`, mapped to its owning module name.
 *
 * @return array<string, string>
 */
function moduleSourceFiles(): array
{
    $files = [];

    foreach (File::allFiles(base_path('app-modules')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $module = str($file->getRelativePathname())->before(DIRECTORY_SEPARATOR)->toString();

        $files[$file->getRealPath()] = $module;
    }

    return $files;
}

/**
 * Derive the fully-qualified class name for a file under `app-modules/`.
 */
function moduleFqcn(string $absolutePath): string
{
    $relative = str($absolutePath)->after('app-modules'.DIRECTORY_SEPARATOR)->before('.php');

    return 'Modules\\'.$relative->replace(DIRECTORY_SEPARATOR, '\\')->toString();
}

/**
 * @return list<string> every imported symbol in the file
 */
function importsOf(string $absolutePath): array
{
    preg_match_all('/^use\s+(?:function\s+|const\s+)?([^;]+);/m', File::get($absolutePath), $matches);

    return array_map(trim(...), $matches[1]);
}

it('modules never import another module', function (): void {
    $violations = [];

    foreach (moduleSourceFiles() as $path => $module) {
        preg_match_all('/^use\s+Modules\\\\([A-Za-z0-9_]+)\\\\/m', File::get($path), $matches);

        foreach ($matches[1] as $imported) {
            if ($imported !== $module) {
                $violations[] = sprintf('%s imports Modules\\%s', $path, $imported);
            }
        }
    }

    expect($violations)->toBe([]);
});

it('domain layers depend only on domain-safe namespaces', function (): void {
    $forbiddenPrefixes = [
        'Inertia\\',
        'Illuminate\\Http\\',
        'Illuminate\\Routing\\',
        'Spatie\\LaravelData\\',
    ];

    $violations = [];

    foreach (moduleSourceFiles() as $path => $module) {
        if (! str_contains($path, DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $ownLayers = [
            'Modules\\'.$module.'\\Http\\',
            'Modules\\'.$module.'\\Actions\\',
            'Modules\\'.$module.'\\Queries\\',
            'Modules\\'.$module.'\\Data\\',
            'Modules\\'.$module.'\\Infrastructure\\Repositories\\',
        ];

        foreach (importsOf($path) as $import) {
            foreach ([...$forbiddenPrefixes, ...$ownLayers] as $prefix) {
                if (str_starts_with($import, $prefix)) {
                    $violations[] = sprintf('%s imports %s', $path, $import);
                }
            }
        }
    }

    expect($violations)->toBe([]);
});

it('value objects are final and readonly', function (): void {
    $violations = [];

    foreach (array_keys(moduleSourceFiles()) as $path) {
        if (! str_contains($path, DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'ValueObjects'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $class = moduleFqcn($path);

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if (! $reflection->isFinal() || ! $reflection->isReadonly()) {
            $violations[] = $class.' must be final and readonly';
        }
    }

    expect($violations)->toBe([]);
});

it('every domain repository contract has an eloquent implementation', function (): void {
    $violations = [];

    foreach (array_keys(moduleSourceFiles()) as $path) {
        if (! str_contains($path, DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Repositories'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $contract = moduleFqcn($path);

        if (! interface_exists($contract)) {
            continue;
        }

        $implementation = str($contract)
            ->replace('\\Domain\\Repositories\\', '\\Infrastructure\\Repositories\\Eloquent')
            ->toString();

        if (! class_exists($implementation) || ! is_subclass_of($implementation, $contract)) {
            $violations[] = sprintf('%s has no Eloquent implementation (%s)', $contract, $implementation);
        }
    }

    expect($violations)->toBe([]);
});
