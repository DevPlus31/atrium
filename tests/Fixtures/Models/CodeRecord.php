<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A model keyed by free-form strings, for tests of key-shape handling.
 */
#[WithoutIncrementing]
#[WithoutTimestamps]
final class CodeRecord extends Model
{
    use HasFactory;
    use HasFactory;

    protected $keyType = 'string';
}
