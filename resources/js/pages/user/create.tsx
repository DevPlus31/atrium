import { Form, Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { AuthSubmit } from '@/components/auth/auth-submit';
import { FormField } from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    const { t } = useLaravelReactI18n();

    return (
        <AuthLayout
            title={t('Create your account')}
            description={t(
                'It takes a minute. An admin grants your access afterwards.',
            )}
        >
            <Head title={t('Register')} />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <FormField
                                id="name"
                                label={t('Name')}
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    name="name"
                                    placeholder={t('Full name')}
                                />
                            </FormField>

                            <FormField
                                id="email"
                                label={t('Email address')}
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    name="email"
                                    placeholder={t('email@example.com')}
                                />
                            </FormField>

                            <FormField
                                id="password"
                                label={t('Password')}
                                error={errors.password}
                            >
                                <PasswordInput
                                    id="password"
                                    required
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder={t('Password')}
                                />
                            </FormField>

                            <FormField
                                id="password_confirmation"
                                label={t('Confirm password')}
                                error={errors.password_confirmation}
                            >
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder={t('Confirm password')}
                                />
                            </FormField>

                            <AuthSubmit
                                processing={processing}
                                test="register-user-button"
                                className="mt-2"
                            >
                                {t('Create account')}
                            </AuthSubmit>
                        </div>

                        <p className="text-sm text-muted-foreground">
                            {t('Already have an account?')}{' '}
                            <TextLink href={login()}>{t('Log in')}</TextLink>
                        </p>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
