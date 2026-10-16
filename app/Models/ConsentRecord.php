<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsentCategory;
use Carbon\CarbonInterface;
use Database\Factories\ConsentRecordFactory;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Proof of one cookie choice (GDPR Art. 7(1)): which browser (visitor_id)
 * and, when signed in, which account chose what, against which version of
 * the cookie policy, and whether a Global Privacy Control signal was sent.
 * Records are never edited: a new choice is a new record.
 *
 * @property-read string $id
 * @property-read string $visitor_id
 * @property-read string|null $user_id
 * @property-read string $version
 * @property-read Collection<int, ConsentCategory> $granted
 * @property-read bool $gpc
 * @property-read CarbonInterface $created_at
 */
final class ConsentRecord extends Model
{
    /** @use HasFactory<ConsentRecordFactory> */
    use HasFactory;

    use HasUuids;

    public const null UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'visitor_id' => 'string',
            'user_id' => 'string',
            'version' => 'string',
            'granted' => AsEnumCollection::of(ConsentCategory::class),
            'gpc' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
