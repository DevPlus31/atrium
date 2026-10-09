import { Form, Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { AuthSubmit } from '@/components/auth/auth-submit';
import { FormField } from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/password/confirm';

export default function Create() {
    const { t } = useLaravelReactI18n();

    return (
        <AuthLayout
            title={t('Confirm your password')}
            description={t(
                'This is a secure area of the application. Please confirm your password before continuing.',
            )}
        >
            <Head title={t('Confirm password')} />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <FormField
                            id="password"
                            label={t('Password')}
                            error={errors.password}
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={t('Password')}
                                autoComplete="current-password"
                                autoFocus
                            />
                        </FormField>

                        <div className="flex items-center">
                            <AuthSubmit
                                processing={processing}
                                test="confirm-password-button"
                            >
                                {t('Confirm password')}
                            </AuthSubmit>
                        </div>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
