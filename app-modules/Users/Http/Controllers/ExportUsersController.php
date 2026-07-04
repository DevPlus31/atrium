<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Users\Queries\UsersIndexQuery;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Authorize('export', User::class)]
final readonly class ExportUsersController
{
    public function __invoke(Request $request, UsersIndexQuery $query): StreamedResponse
    {
        activity('users')
            ->event('exported')
            ->withProperties(['filter' => (array) $request->query('filter', [])])
            ->log('exported');

        return response()->streamDownload(
            fn () => $this->write($query),
            'users.csv',
            ['Content-Type' => 'text/csv'],
        );
    }

    private function write(UsersIndexQuery $query): void
    {
        $writer = SimpleExcelWriter::streamDownload('users.csv');

        foreach ($query->builder()->lazy() as $user) {
            $writer->addRow([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String() ?? '',
                'roles' => $user->roles->pluck('name')->sort()->values()->implode(', '),
                'created_at' => $user->created_at->toIso8601String(),
            ]);
        }

        $writer->close();
    }
}
