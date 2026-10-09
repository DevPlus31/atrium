import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { TimezoneSelect } from '@/components/timezone-select';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useLocalePreference } from '@/hooks/use-locale';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as editAppearance } from '@/routes/appearance';
import type { BreadcrumbItem } from '@/types';

export default function Update() {
    const { t } = useLaravelReactI18n();
    const { locale, locales, updateLocale } = useLocalePreference();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('Appearance settings'),
            href: editAppearance(),
        },
    ];
    useBreadcrumbs(breadcrumbs);

    return (
        <>
            <Head title={t('Appearance settings')} />

            <h1 className="sr-only">{t('Appearance settings')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('Appearance settings')}
                        description={t(
                            "Update your account's appearance settings",
                        )}
                    />
                    <AppearanceTabs />
                </div>

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('Language and region')}
                        description={t(
                            'Choose the language and the timezone dates are shown in',
                        )}
                    />

                    <div className="grid gap-2">
                        <Label htmlFor="language">{t('Language')}</Label>
                        <Select value={locale} onValueChange={updateLocale}>
                            <SelectTrigger
                                id="language"
                                className="w-full sm:w-80"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(locales).map(
                                    ([code, label]) => (
                                        <SelectItem key={code} value={code}>
                                            {label}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="timezone">{t('Timezone')}</Label>
                        <TimezoneSelect id="timezone" />
                    </div>
                </div>
            </SettingsLayout>
        </>
    );
}
