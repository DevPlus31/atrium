<?php

declare(strict_types=1);

namespace App\Modules;

use Illuminate\Support\Facades\Config;
use Laravel\Pennant\Feature;

/**
 * Whether a module is on for a given scope: not disabled for everyone in
 * config/modules.php, and its Pennant flag `module:<name>` active for the
 * scope (usually the user), so a module can also be rolled out per user.
 */
final class ModuleSwitch
{
    public static function isOn(string $module, mixed $scope): bool
    {
        if (in_array($module, Config::array('modules.disabled'), true)) {
            return false;
        }

        $features = Feature::for($scope);

        // One query for every module's flag (Pennant keeps them for the rest
        // of the request) rather than one per module asked about.
        $features->loadMissing(array_values(array_filter(
            Feature::defined(),
            static fn (mixed $feature): bool => is_string($feature) && str_starts_with($feature, 'module:'),
        )));

        return $features->active('module:'.$module);
    }
}
