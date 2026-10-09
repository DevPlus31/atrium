import { createInertiaApp } from '@inertiajs/react';
import { LaravelReactI18nProvider } from 'laravel-react-i18n';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { listenForFlashToasts } from '@/lib/flash-toasts';
import { resolveLayout, resolvePage } from '@/lib/resolve-page';
import { translationFiles } from '@/lib/translations';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

listenForFlashToasts();

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: resolvePage,
    layout: resolveLayout,
    setup({ el, App, props }) {
        const root = createRoot(el);
        const { locale } = props.initialPage.props;

        root.render(
            <StrictMode>
                <LaravelReactI18nProvider
                    locale={typeof locale === 'string' ? locale : 'en'}
                    fallbackLocale="en"
                    files={translationFiles}
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
        color: 'var(--muted-foreground)',
    },
});

// This will set light / dark mode on load...
initializeTheme();
