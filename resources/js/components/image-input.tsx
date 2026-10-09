import { router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useId, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type ImageInputProps = {
    /** The current image, or null to show the fallback. */
    imageUrl: string | null;
    /** Shown when there is no image (initials, an icon…). */
    fallback: ReactNode;
    /** Accessible name of the file picker, e.g. "Profile photo". */
    label: string;
    /** File types offered by the picker; the server validates them again. */
    accept?: string;
    shape?: 'circle' | 'square';
    /** The request field the file is sent as; its validation error shows here. */
    field: string;
    /** Receives the file as multipart POST (PUT cannot carry files). */
    uploadUrl: string;
    /** Deletes the image; no "Remove" button without it. */
    removeUrl?: string;
};

/**
 * A preview with "Upload"/"Change" and "Remove" buttons around a hidden
 * file input. It uploads the chosen file to `uploadUrl` and removes the
 * image through `removeUrl`, keeping the page's scroll position.
 */
export function ImageInput({
    imageUrl,
    fallback,
    label,
    accept = 'image/jpeg,image/png,image/webp',
    shape = 'circle',
    field,
    uploadUrl,
    removeUrl,
}: ImageInputProps) {
    const { t } = useLaravelReactI18n();
    const { errors } = usePage().props;
    const input = useRef<HTMLInputElement>(null);
    const id = useId();
    const [processing, setProcessing] = useState(false);

    const upload = (file: File) =>
        router.post(
            uploadUrl,
            { [field]: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );

    return (
        <div className="grid gap-2">
            <div className="flex items-center gap-4">
                <div
                    className={cn(
                        'flex size-16 shrink-0 items-center justify-center overflow-hidden border bg-muted text-muted-foreground',
                        shape === 'circle' ? 'rounded-full' : 'rounded-md',
                    )}
                >
                    {imageUrl ? (
                        <img
                            src={imageUrl}
                            alt=""
                            className="size-full object-cover"
                        />
                    ) : (
                        fallback
                    )}
                </div>

                <input
                    ref={input}
                    id={id}
                    type="file"
                    accept={accept}
                    aria-label={label}
                    className="sr-only"
                    onChange={(event) => {
                        const file = event.target.files?.[0];

                        if (file) {
                            upload(file);
                        }

                        event.target.value = '';
                    }}
                />

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={processing}
                    onClick={() => input.current?.click()}
                >
                    {processing && <Spinner />}
                    {imageUrl ? t('Change') : t('Upload')}
                </Button>

                {imageUrl && removeUrl && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={processing}
                        onClick={() =>
                            router.delete(removeUrl, { preserveScroll: true })
                        }
                    >
                        {t('Remove')}
                    </Button>
                )}
            </div>

            <InputError message={errors[field]} />
        </div>
    );
}
