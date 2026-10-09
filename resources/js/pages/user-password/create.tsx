import { Form, Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { AuthSubmit } from '@/components/auth/auth-submit';
import { FormField } from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
};

export default function ResetPassword({ token, email }: Props) {
    const { t } = useLaravelReactI18n();

    return (
        <AuthLayout
            title={t('Reset password')}
            description={t('Please enter your new password below')}
        >
            <Head title={t('Reset password')} />

            <Form
                {...update.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <FormField
                            id="email"
                            label={t('Email')}
                            error={errors.email}
                        >
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                value={email}
                                readOnly
                            />
                        </FormField>

                        <FormField
                            id="password"
                            label={t('Password')}
                            error={errors.password}
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                autoFocus
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
                                name="password_confirmation"
                                autoComplete="new-password"
                                placeholder={t('Confirm password')}
                            />
                        </FormField>

                        <AuthSubmit
                            processing={processing}
                            test="reset-password-button"
                            className="mt-4"
                        >
                            {t('Reset password')}
                        </AuthSubmit>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
