<?php

declare(strict_types=1);

namespace App\Console\Commands\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Shared by the module commands: turning an argument into a safe StudlyCase
 * name (it ends up in paths and namespaces) and rendering the stubs/ddd files.
 */
trait GeneratesModuleCode
{
    /**
     * The argument in StudlyCase, or null (with an error printed) when it is
     * not a plain name: letters and digits, starting with a letter.
     */
    private function studlyArgument(string $key): ?string
    {
        /** @var string $value */
        $value = $this->argument($key);
        $studly = Str::studly($value);

        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $studly) !== 1) {
            $this->components->error(sprintf('[%s] is not a valid name: use letters and digits, starting with a letter.', $value));

            return null;
        }

        return $studly;
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
}
