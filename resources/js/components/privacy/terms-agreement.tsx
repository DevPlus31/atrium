import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { CheckboxField } from '@/components/checkbox-field';
import { show } from '@/routes/legal';

/**
 * The "I accept the terms" checkbox of the sign-up and terms pages, sent as
 * `terms`, with links to read both documents (in a new tab, so the form
 * stays filled in).
 */
export function TermsAgreement({ error }: { error?: string }) {
    const { t } = useLaravelReactI18n();
    const [accepted, setAccepted] = useState(false);

    const documentLink = (href: string, label: string) => (
        <a
            href={href}
            target="_blank"
            rel="noreferrer"
            className="font-medium text-foreground underline underline-offset-4"
        >
            {label}
        </a>
    );

    return (
        <CheckboxField
            id="terms"
            name="terms"
            label={t('I accept the Terms of Service and the Privacy Policy')}
            hint={
                <>
                    {documentLink(show.url('terms'), t('Terms of Service'))}
                    {' · '}
                    {documentLink(show.url('privacy'), t('Privacy Policy'))}
                </>
            }
            error={error}
            checked={accepted}
            onCheckedChange={setAccepted}
        />
    );
}
