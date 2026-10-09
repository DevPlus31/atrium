<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Http\UploadedFile;

final readonly class UpdateAvatar
{
    /**
     * Store the new profile photo (the collection keeps a single file, so the
     * previous photo and its thumbnail are removed) and record the change.
     */
    public function handle(User $user, UploadedFile $photo): void
    {
        // The library prunes the old photo through the model's loaded media
        // list; a list loaded before this upload would miss it.
        $user->unsetRelation('media');

        $user->addMedia($photo)
            ->usingFileName('avatar.'.$photo->extension())
            ->toMediaCollection(User::AVATAR);

        AuditLog::record(
            log: 'users',
            event: 'avatar-updated',
            subject: $user,
        );
    }
}
