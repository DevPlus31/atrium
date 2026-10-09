// Credit: https://usehooks-ts.com/
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { toast } from 'sonner';

export type CopiedValue = string | null;
export type CopyFn = (text: string) => Promise<boolean>;
export type UseClipboardReturn = [CopiedValue, CopyFn];

/**
 * Copy text to the clipboard. When the browser refuses (no permission, an
 * insecure page) the user is told, so they can select the text instead.
 */
export function useClipboard(): UseClipboardReturn {
    const { t } = useLaravelReactI18n();
    const [copiedText, setCopiedText] = useState<CopiedValue>(null);

    const copy: CopyFn = async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            setCopiedText(text);

            return true;
        } catch {
            setCopiedText(null);
            toast.error(
                t(
                    'Could not copy to the clipboard. Select the text and copy it instead.',
                ),
            );

            return false;
        }
    };

    return [copiedText, copy];
}
