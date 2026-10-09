<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use App\Modules\Concerns\ValidatesBulkSelection;
use Illuminate\Foundation\Http\FormRequest;

final class BulkSelectionRequest extends FormRequest
{
    use ValidatesBulkSelection;
}
