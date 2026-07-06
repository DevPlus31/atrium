import { createInertiaApp } from '@inertiajs/react';
import { LaravelReactI18nProvider } from 'laravel-react-i18n';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { resolvePage } from '@/lib/resolve-page';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: resolvePage,
    setup({ el, App, props }) {
        const root = createRoot(el);
        const { locale } = props.initialPage.props;

        root.render(
            <StrictMode>
                <LaravelReactI18nProvider
                    locale={typeof locale === 'string' ? locale : 'en'}
                    fallbackLocale="en"
                    files={import.meta.glob('/lang/*.json', { eager: true })}
                >
                    <TooltipProvider delayDuration={0}>
                        <App {...props} />
                        <Toaster />
                    </TooltipProvider>
                </LaravelReactI18nProvider>
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
