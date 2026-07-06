import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { LaravelReactI18nProvider } from 'laravel-react-i18n';
import ReactDOMServer from 'react-dom/server';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { resolvePage } from '@/lib/resolve-page';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: resolvePage,
        setup: ({ App, props }) => {
            const { locale } = props.initialPage.props;

            return (
                <LaravelReactI18nProvider
                    ssr
                    locale={typeof locale === 'string' ? locale : 'en'}
                    fallbackLocale="en"
                    files={import.meta.glob('/lang/*.json', { eager: true })}
                >
                    <TooltipProvider delayDuration={0}>
                        <App {...props} />
                        <Toaster />
                    </TooltipProvider>
                </LaravelReactI18nProvider>
            );
        },
    }),
);
