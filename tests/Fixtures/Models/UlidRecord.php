<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A model keyed by ULIDs, for tests of key-shape handling.
 */
#[WithoutTimestamps]
final class UlidRecord extends Model
{
    use HasFactory;
    use HasFactory;
    use HasUlids;
}
