import { useLaravelReactI18n } from 'laravel-react-i18n';
import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { t } = useLaravelReactI18n();

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5 fill-current" />
            </div>
            <div className="ms-1 grid flex-1 text-start text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {t('Laravel Starter Kit')}
                </span>
            </div>
        </>
    );
}
