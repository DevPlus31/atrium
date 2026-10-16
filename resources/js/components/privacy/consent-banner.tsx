import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Cookie, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { ConsentForm } from '@/components/privacy/consent-form';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { saveConsent, useConsent } from '@/hooks/use-consent';
import { show } from '@/routes/legal';

/**
 * Asks for cookie consent until the visitor chooses (for the current
 * cookie policy version). "Reject all" is as easy as "Accept all", as
 * European regulators require; "Customize" picks category by category.
 * Every layout renders it; it stays out of the way, fixed to the bottom.
 */
export function ConsentBanner() {
    const { t } = useLaravelReactI18n();
    const consent = useConsent();
    const [customizing, setCustomizing] = useState(false);

    if (consent.decided) {
        return null;
    }

    const choose = (granted: App.Enums.ConsentCategory[]) =>
        saveConsent(granted, {
            // Gone at once; Inertia restores it if the request fails.
            optimistic: (props) => ({
                consent: { ...props.consent, decided: true },
            }),
        });

    return (
        <section
            aria-labelledby="consent-banner-title"
            data-test="consent-banner"
            className="fixed inset-x-4 bottom-4 z-50 mx-auto max-w-xl rounded-xl border bg-card p-5 text-card-foreground shadow-lg motion-safe:animate-in motion-safe:duration-300 motion-safe:fade-in motion-safe:slide-in-from-bottom-4 sm:inset-x-auto sm:end-4 sm:mx-0"
        >
            <div className="flex items-start gap-3">
                <Cookie
                    aria-hidden
                    className="mt-0.5 size-5 shrink-0 text-primary"
                />
                <div className="grid gap-2">
                    <h2
                        id="consent-banner-title"
                        className="font-display text-xl leading-tight tracking-tight"
                    >
                        {t('Your privacy')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {t(
                            'We use essential cookies to make this app work. With your consent, we would also use optional cookies to understand how it is used and for marketing. You can change your mind at any time.',
                        )}{' '}
                        <Link
                            href={show('cookies')}
                            className="font-medium text-foreground underline underline-offset-4"
                        >
                            {t('Cookie Policy')}
                        </Link>
                    </p>
                    {consent.gpc && (
                        <p className="flex items-start gap-2 text-sm text-foreground">
                            <ShieldCheck
                                aria-hidden
                                className="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            {t(
                                'Your browser’s Global Privacy Control signal is on, so we won’t sell or share your personal information.',
                            )}
                        </p>
                    )}
                </div>
            </div>

            <div className="mt-4 flex flex-wrap justify-end gap-2">
                <Button
                    variant="ghost"
                    onClick={() => setCustomizing(true)}
                    data-test="consent-customize"
                >
                    {t('Customize')}
                </Button>
                <Button
                    onClick={() => choose([])}
                    data-test="consent-reject-all"
                >
                    {t('Reject all')}
                </Button>
                <Button
                    onClick={() =>
                        choose(
                            consent.categories.map(
                                (category) => category.value,
                            ),
                        )
                    }
                    data-test="consent-accept-all"
                >
                    {t('Accept all')}
                </Button>
            </div>

            <Dialog open={customizing} onOpenChange={setCustomizing}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Cookie settings')}</DialogTitle>
                        <DialogDescription>
                            {t(
                                'Choose which optional cookies we may use. Strictly necessary cookies are always on.',
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <ConsentForm onSaved={() => setCustomizing(false)} />
                </DialogContent>
            </Dialog>
        </section>
    );
}
