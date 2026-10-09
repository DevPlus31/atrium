import { useLaravelReactI18n } from 'laravel-react-i18n';
import AppearanceTabs from '@/components/appearance-tabs';
import { FormField } from '@/components/form-field';
import { SelectField } from '@/components/select-field';
import { SettingsSection } from '@/components/settings-section';
import { TimezoneSelect } from '@/components/timezone-select';
import { useLocalePreference } from '@/hooks/use-locale';
import { SettingsPage } from '@/layouts/settings/page';
import { edit as editAppearance } from '@/routes/appearance';

export default function Update() {
    const { t } = useLaravelReactI18n();
    const { locale, locales, updateLocale } = useLocalePreference();

    return (
        <>
            <SettingsPage
                title={t('Appearance settings')}
                href={editAppearance()}
            >
                <SettingsSection
                    title={t('Appearance settings')}
                    description={t("Update your account's appearance settings")}
                >
                    <AppearanceTabs />
                </SettingsSection>

                <SettingsSection
                    title={t('Language and region')}
                    description={t(
                        'Choose the language and the timezone dates are shown in',
                    )}
                >
                    <SelectField
                        id="language"
                        label={t('Language')}
                        value={locale}
                        onValueChange={updateLocale}
                        options={Object.entries(locales).map(
                            ([code, label]) => ({ value: code, label }),
                        )}
                        triggerClassName="w-full sm:w-80"
                    />

                    <FormField id="timezone" label={t('Timezone')}>
                        <TimezoneSelect id="timezone" />
                    </FormField>
                </SettingsSection>
            </SettingsPage>
        </>
    );
}
