<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\User;
use App\Modules\SearchRegistry;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * The command palette's global search. Answers JSON: the palette calls it
 * with Inertia's useHttp, outside any page visit.
 */
final readonly class SearchController
{
    public function __invoke(SearchRequest $request, #[CurrentUser] User $user, SearchRegistry $search): JsonResponse
    {
        return response()->json([
            'groups' => $search->search($user, $request->term()),
        ]);
    }
}
