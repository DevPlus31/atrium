import { Head, Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { BrandMark } from '@/components/brand-mark';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

/**
 * @branding The page every 403, 404, 429, 500 and 503 renders as (see
 * AppServiceProvider::renderErrorPage). It uses the brand mark, the app name
 * and the display font; adjust the copy below to your product's voice.
 */
const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'You don’t have access to this page',
        description:
            'Your account isn’t allowed to open it. If you think it should be, ask an administrator.',
    },
    404: {
        title: 'Page not found',
        description: 'The page you’re looking for doesn’t exist or has moved.',
    },
    429: {
        title: 'Too many requests',
        description: 'Please wait a moment before trying again.',
    },
    500: {
        title: 'Something went wrong',
        description:
            'An error on our side stopped this page from loading. Please try again shortly.',
    },
    503: {
        title: 'Down for maintenance',
        description: 'We’re making improvements and will be back soon.',
    },
};

export default function ErrorPage({ status }: { status: number }) {
    const { t } = useLaravelReactI18n();
    const { name, supportEmail } = usePage().props;
    const { url } = usePage();
    const message = messages[status] ?? messages[500];

    return (
        <>
            <Head title={t(message.title)} />
            <main className="flex min-h-svh flex-col items-center justify-center gap-10 bg-background px-6 py-12 text-center text-foreground">
                <Link
                    href={home()}
                    className="flex items-center gap-2 text-sm font-medium"
                >
                    <BrandMark className="size-7 text-primary" />
                    {name}
                </Link>

                <div className="flex max-w-md flex-col items-center gap-3">
                    <p
                        className="font-display text-8xl leading-none tracking-tight text-primary tabular-nums"
                        aria-hidden="true"
                    >
                        {status}
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight text-balance">
                        {t(message.title)}
                    </h1>
                    <p className="text-pretty text-muted-foreground">
                        {t(message.description)}
                    </p>
                    {supportEmail !== null && status !== 404 && (
                        <p className="text-sm text-muted-foreground">
                            {t('Need help? Contact')}{' '}
                            <a
                                href={`mailto:${supportEmail}`}
                                className="font-medium text-foreground underline underline-offset-4"
                            >
                                {supportEmail}
                            </a>
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap items-center justify-center gap-3">
                    {status === 503 ? (
                        <Button asChild>
                            <a href={url}>{t('Try again')}</a>
                        </Button>
                    ) : (
                        <>
                            <Button
                                variant="outline"
                                onClick={() => window.history.back()}
                            >
                                {t('Go back')}
                            </Button>
                            <Button asChild>
                                <Link href={home()}>{t('Go to home')}</Link>
                            </Button>
                        </>
                    )}
                </div>
            </main>
        </>
    );
}
