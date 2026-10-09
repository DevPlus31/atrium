<?php

declare(strict_types=1);

namespace App\Modules;

use Composer\Autoload\ClassLoader;
use SplFileInfo;

/**
 * Maps a listener file found by Laravel's event discovery to its class name
 * through Composer's PSR-4 prefixes, so listeners in app-modules/<Module>/
 * Listeners resolve to Modules\<Module>\Listeners\… (Laravel's default
 * mapping only understands app/). A file that is not an autoloadable class
 * maps to this resolver, which handles no event, so discovery skips it.
 */
final class ListenerClassResolver
{
    /**
     * @return class-string
     */
    public static function classFromFile(SplFileInfo $file): string
    {
        $path = (string) $file->getRealPath();
        $match = '';
        $matchedDirectory = '';

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            foreach ($loader->getPrefixesPsr4() as $prefix => $directories) {
                foreach ($directories as $directory) {
                    $directory = (string) realpath($directory);

                    if ($directory !== '' && str_starts_with($path, $directory.DIRECTORY_SEPARATOR) && mb_strlen($directory) > mb_strlen($matchedDirectory)) {
                        $matchedDirectory = $directory;
                        $match = $prefix.str_replace(
                            DIRECTORY_SEPARATOR,
                            '\\',
                            mb_substr($path, mb_strlen($directory) + 1, -mb_strlen('.php')),
                        );
                    }
                }
            }
        }

        return class_exists($match) ? $match : self::class;
    }
}
