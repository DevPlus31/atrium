<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ExportPersonalData;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Download everything the app holds about you, as one JSON file.
 */
final readonly class PersonalDataExportController
{
    public function __invoke(Request $request, #[CurrentUser] User $user, ExportPersonalData $export): Response
    {
        // After the password confirmation, Inertia follows the redirect here
        // itself; the browser has to fetch the file, so send it on.
        if ($request->inertia()) {
            return Inertia::location(route('privacy.export'));
        }

        return new JsonResponse($export($user), headers: [
            'Content-Disposition' => sprintf('attachment; filename="personal-data-%s.json"', now()->toDateString()),
        ], options: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
