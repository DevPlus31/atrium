import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useId, useRef } from 'react';
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
    error?: string;
    processing?: boolean;
    onSelect: (file: File) => void;
    onRemove?: () => void;
};

/**
 * A preview with "Upload"/"Change" and "Remove" buttons around a hidden
 * file input. Uploading is the caller's job (e.g. `router.post` with the
 * file), so it fits any endpoint that takes an image.
 */
export function ImageInput({
    imageUrl,
    fallback,
    label,
    accept = 'image/jpeg,image/png,image/webp',
    shape = 'circle',
    error,
    processing = false,
    onSelect,
    onRemove,
}: ImageInputProps) {
    const { t } = useLaravelReactI18n();
    const input = useRef<HTMLInputElement>(null);
    const id = useId();

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
                            onSelect(file);
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

                {imageUrl && onRemove && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={processing}
                        onClick={onRemove}
                    >
                        {t('Remove')}
                    </Button>
                )}
            </div>

            <InputError message={error} />
        </div>
    );
}
