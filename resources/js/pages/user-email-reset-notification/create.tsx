import { Form, Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { AuthStatus } from '@/components/auth/auth-status';
import { AuthSubmit } from '@/components/auth/auth-submit';
import { FormField } from '@/components/form-field';
import TextLink from '@/components/text-link';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const { t } = useLaravelReactI18n();

    return (
        <AuthLayout
            title={t('Forgot password')}
            description={t('Enter your email to receive a password reset link')}
        >
            <Head title={t('Forgot password')} />

            {status && <AuthStatus>{status}</AuthStatus>}

            <div className="space-y-6">
                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="email"
                                label={t('Email address')}
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder={t('email@example.com')}
                                />
                            </FormField>

                            <div className="my-6 flex items-center justify-start">
                                <AuthSubmit
                                    processing={processing}
                                    test="email-password-reset-link-button"
                                >
                                    {t('Email password reset link')}
                                </AuthSubmit>
                            </div>
                        </>
                    )}
                </Form>

                <p className="text-sm text-muted-foreground">
                    {t('Or, return to')}{' '}
                    <TextLink href={login()}>{t('log in')}</TextLink>
                </p>
            </div>
        </AuthLayout>
    );
}
