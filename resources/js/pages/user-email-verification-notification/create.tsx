import { Form, Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { AuthStatus } from '@/components/auth/auth-status';
// Components
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useLaravelReactI18n();

    return (
        <AuthLayout
            title={t('Verify email')}
            description={t(
                'Please verify your email address by clicking on the link we just emailed to you.',
            )}
        >
            <Head title={t('Email verification')} />

            {status === 'verification-link-sent' && (
                <AuthStatus>
                    {t(
                        'A new verification link has been sent to the email address you provided during registration.',
                    )}
                </AuthStatus>
            )}

            <Form
                {...send.form()}
                className="flex flex-wrap items-center gap-4"
            >
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            {t('Resend verification email')}
                        </Button>

                        <TextLink href={logout()} className="text-sm">
                            {t('Log out')}
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
