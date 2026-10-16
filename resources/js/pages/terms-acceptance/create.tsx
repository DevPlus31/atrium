import { Form, Head, Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { AuthSubmit } from '@/components/auth/auth-submit';
import { TermsAgreement } from '@/components/privacy/terms-agreement';
import TextLink from '@/components/text-link';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { edit as privacySettings } from '@/routes/privacy';
import { store } from '@/routes/terms-acceptance';

type TermsAcceptanceProps = {
    /** The account never accepted any version (made by an admin or an invitation). */
    firstTime: boolean;
};

export default function Create({ firstTime }: TermsAcceptanceProps) {
    const { t } = useLaravelReactI18n();

    return (
        <AuthLayout
            title={
                firstTime
                    ? t('Before you continue')
                    : t('We’ve updated our terms')
            }
            description={
                firstTime
                    ? t('Please read and accept our terms to use the app.')
                    : t(
                          'Please read and accept the new version to keep using the app.',
                      )
            }
        >
            <Head title={t('Terms of Service')} />
            <Form
                {...store.form()}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <TermsAgreement error={errors.terms} />

                        <AuthSubmit
                            processing={processing}
                            test="accept-terms-button"
                        >
                            {t('Continue')}
                        </AuthSubmit>
                    </>
                )}
            </Form>

            <div className="mt-6 grid gap-2 text-sm text-muted-foreground">
                <p>
                    {t(
                        'Don’t agree? You can still download your data or delete your account.',
                    )}
                </p>
                <p className="flex flex-wrap gap-x-4">
                    <TextLink href={privacySettings()}>
                        {t('Privacy settings')}
                    </TextLink>
                    <Link
                        href={logout()}
                        as="button"
                        className="text-foreground underline decoration-muted-foreground/50 underline-offset-4"
                        data-test="terms-logout"
                    >
                        {t('Log out')}
                    </Link>
                </p>
            </div>
        </AuthLayout>
    );
}
