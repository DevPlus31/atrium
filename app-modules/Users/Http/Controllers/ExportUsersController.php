<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Users\Actions\RecordUsersExport;
use Modules\Users\Queries\UsersIndexQuery;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Authorize('export', User::class)]
final readonly class ExportUsersController
{
    public function __invoke(Request $request, UsersIndexQuery $query, RecordUsersExport $record): StreamedResponse
    {
        $record->handle((array) $request->query('filter', []));

        return response()->streamDownload(
            fn () => $this->write($query),
            'users.csv',
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * Prefix values a spreadsheet would evaluate as a formula, so a crafted
     * name such as `=HYPERLINK(...)` opens as plain text (CSV injection).
     */
    private function text(string $value): string
    {
        return in_array(mb_substr($value, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }

    private function write(UsersIndexQuery $query): void
    {
        $writer = SimpleExcelWriter::streamDownload('users.csv');

        foreach ($query->builder()->lazy() as $user) {
            $writer->addRow([
                'id' => $user->id,
                'name' => $this->text($user->name),
                'email' => $this->text($user->email),
                'email_verified_at' => $user->email_verified_at?->toIso8601String() ?? '',
                'roles' => $this->text($user->roles->pluck('name')->sort()->values()->implode(', ')),
                'created_at' => $user->created_at->toIso8601String(),
            ]);
        }

        $writer->close();
    }
}
