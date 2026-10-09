<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Every literal string the app translates — `t()`/`tChoice()` in React and
 * `__()`/`trans()`/`trans_choice()` in PHP and Blade — must be in an English catalogue translators copy when
 * adding a locale: the shell's lang/en.json, or for module code that
 * module's own lang/en.json (a module never relies on another's strings).
 * Dotted PHP keys (`auth.failed`) live in PHP lang files and are skipped.
 */
function missingTranslations(string $directory, array $catalogue, array $excluded = []): array
{
    $patterns = [
        '/\b(?:t|tChoice)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s' => ['*.ts', '*.tsx'],
        '/\b(?:__|trans|trans_choice)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s' => ['*.php'],
    ];

    $missing = [];

    foreach ($patterns as $pattern => $names) {
        $files = Finder::create()->in($directory)->name($names)->notPath($excluded);

        foreach ($files as $file) {
            preg_match_all($pattern, $file->getContents(), $matches);

            foreach ($matches[2] as $key) {
                $key = stripslashes($key);

                if (! array_key_exists($key, $catalogue) && ! preg_match('/^[\w-]+(\.[\w-]+)+$/', $key)) {
                    $missing[$key] = $file->getRelativePathname();
                }
            }
        }
    }

    return $missing;
}

/**
 * @return array<string, string>
 */
function englishCatalogue(string $path): array
{
    if (! is_file($path)) {
        return [];
    }

    /** @var array<string, string> $strings */
    $strings = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    return $strings;
}

it('lists every string the shell translates in lang/en.json', function (): void {
    $shell = englishCatalogue(base_path('lang/en.json'));

    expect(missingTranslations(base_path('resources/js'), $shell, ['routes', 'actions', 'wayfinder', 'components/ui', 'types']))->toBe([])
        ->and(missingTranslations(base_path('app'), $shell))->toBe([])
        ->and(missingTranslations(base_path('resources/views'), $shell))->toBe([]);
});

it('lists every string a module translates in the shell or the module catalogue', function (string $module): void {
    $catalogue = [
        ...englishCatalogue(base_path('lang/en.json')),
        ...englishCatalogue(base_path("app-modules/{$module}/lang/en.json")),
    ];

    expect(missingTranslations(base_path("app-modules/{$module}"), $catalogue, ['tests']))->toBe([]);
})->with(array_map(basename(...), glob(dirname(__DIR__, 2).'/app-modules/*', GLOB_ONLYDIR) ?: []));

/**
 * Every catalogue to keep in step: the shell's and each module's lang folder.
 *
 * @return list<string>
 */
function catalogueDirectories(): array
{
    return [base_path('lang'), ...(glob(base_path('app-modules/*/lang'), GLOB_ONLYDIR) ?: [])];
}

/**
 * The locales besides English (config/app.php `available_locales`), read
 * from the file since datasets are built before the app boots.
 *
 * @return list<string>
 */
function otherLocales(): array
{
    /** @var array{available_locales: array<string, string>} $config */
    $config = require dirname(__DIR__, 2).'/config/app.php';

    return array_values(array_diff(array_keys($config['available_locales']), ['en']));
}

/**
 * The `:placeholders` of a string, sorted, so a translation keeps them all.
 *
 * @return list<string>
 */
function placeholdersOf(string $text): array
{
    preg_match_all('/:[a-z_]+/', $text, $matches);
    $placeholders = $matches[0];
    sort($placeholders);

    return $placeholders;
}

/**
 * @param  array<array-key, mixed>  $lines
 * @return list<string>
 */
function translationKeyPaths(array $lines, string $prefix = ''): array
{
    $paths = [];

    foreach ($lines as $key => $line) {
        $path = $prefix.$key;
        $paths = [...$paths, ...(is_array($line) ? translationKeyPaths($line, $path.'.') : [$path])];
    }

    return $paths;
}

it('translates every English string, keeping its placeholders', function (string $locale): void {
    foreach (catalogueDirectories() as $directory) {
        $english = englishCatalogue($directory.'/en.json');
        $translated = englishCatalogue(sprintf('%s/%s.json', $directory, $locale));

        expect(array_keys($translated))->toEqualCanonicalizing(array_keys($english), $directory)
            ->and(array_filter($translated, fn (string $line): bool => mb_trim($line) === ''))->toBe([]);

        foreach ($english as $key => $line) {
            expect(placeholdersOf($translated[$key]))->toBe(placeholdersOf($line), sprintf('%s: %s', $directory, $key));
        }
    }
})->with(otherLocales());

it("translates Laravel's own messages (validation, auth, passwords, pagination)", function (string $locale): void {
    $framework = base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en');

    foreach (['auth', 'pagination', 'passwords', 'validation'] as $group) {
        /** @var array<array-key, mixed> $english */
        $english = require sprintf('%s/%s.php', $framework, $group);
        /** @var array<array-key, mixed> $translated */
        $translated = require base_path(sprintf('lang/%s/%s.php', $locale, $group));

        $expected = array_filter(
            translationKeyPaths($english),
            fn (string $path): bool => ! str_starts_with($path, 'custom.') && ! str_starts_with($path, 'attributes.'),
        );

        expect(array_diff($expected, translationKeyPaths($translated)))->toBe([], $group);
    }
})->with(otherLocales());
