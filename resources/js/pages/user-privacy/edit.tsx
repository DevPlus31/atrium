import { Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Download, ShieldCheck } from 'lucide-react';
import PersonalDataExportController from '@/actions/App/Http/Controllers/PersonalDataExportController';
import DeleteUser from '@/components/delete-user';
import { ItemList, ItemRow } from '@/components/item-list';
import { ConsentForm } from '@/components/privacy/consent-form';
import { SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';
import { useFormatters } from '@/hooks/use-formatters';
import { SettingsPage } from '@/layouts/settings/page';
import { show } from '@/routes/legal';
import { edit } from '@/routes/privacy';

type PrivacyProps = {
    /** When the current account accepted the Terms of Service, if ever. */
    termsAcceptedAt: string | null;
};

export default function Edit({ termsAcceptedAt }: PrivacyProps) {
    const { t } = useLaravelReactI18n();
    const { dateTime } = useFormatters();
    const { consent } = usePage().props;

    const documents = [
        { document: 'terms', title: t('Terms of Service') },
        { document: 'privacy', title: t('Privacy Policy') },
        { document: 'cookies', title: t('Cookie Policy') },
    ] as const;

    return (
        <SettingsPage title={t('Privacy')} href={edit()}>
            <SettingsSection
                title={t('Download your data')}
                description={t(
                    'A copy of everything we hold about you: your account, sessions, notifications, cookie choices and activity, as a JSON file.',
                )}
            >
                {/* A full page request: the browser saves the file. */}
                <Button asChild variant="outline">
                    <a
                        href={PersonalDataExportController.url()}
                        data-test="download-personal-data"
                    >
                        <Download />
                        {t('Download my data')}
                    </a>
                </Button>
            </SettingsSection>

            {consent.categories.length > 0 && (
                <SettingsSection
                    title={t('Your privacy choices')}
                    description={t(
                        'Choose which optional cookies we may use in this browser.',
                    )}
                >
                    {consent.gpc && (
                        <p className="flex items-start gap-2 text-sm">
                            <ShieldCheck
                                aria-hidden
                                className="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            {t(
                                'Your browser’s Global Privacy Control signal is on, so we won’t sell or share your personal information.',
                            )}
                        </p>
                    )}
                    <ConsentForm />
                </SettingsSection>
            )}

            <SettingsSection
                title={t('Legal documents')}
                description={
                    termsAcceptedAt === null
                        ? undefined
                        : t('You accepted the Terms of Service on :date.', {
                              date: dateTime(termsAcceptedAt),
                          })
                }
            >
                <ItemList>
                    {documents.map(({ document, title }) => (
                        <ItemRow
                            key={document}
                            title={
                                <Link
                                    href={show(document)}
                                    className="hover:underline"
                                >
                                    {title}
                                </Link>
                            }
                        />
                    ))}
                </ItemList>
            </SettingsSection>

            <DeleteUser />
        </SettingsPage>
    );
}
