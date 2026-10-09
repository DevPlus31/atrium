import { Form } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useRef } from 'react';
import UserPasswordController from '@/actions/App/Http/Controllers/UserPasswordController';
import { FormField } from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import { SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';
import { SettingsPage } from '@/layouts/settings/page';
import { edit } from '@/routes/password';

export default function Password() {
    const { t } = useLaravelReactI18n();
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <SettingsPage title={t('Password settings')} href={edit().url}>
                <SettingsSection
                    title={t('Update password')}
                    description={t(
                        'Ensure your account is using a long, random password to stay secure',
                    )}
                >
                    <Form
                        {...UserPasswordController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInput.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInput.current?.focus();
                            }
                        }}
                        className="space-y-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <FormField
                                    id="current_password"
                                    label={t('Current password')}
                                    error={errors.current_password}
                                >
                                    <PasswordInput
                                        id="current_password"
                                        ref={currentPasswordInput}
                                        name="current_password"
                                        autoComplete="current-password"
                                        placeholder={t('Current password')}
                                    />
                                </FormField>

                                <FormField
                                    id="password"
                                    label={t('New password')}
                                    error={errors.password}
                                >
                                    <PasswordInput
                                        id="password"
                                        ref={passwordInput}
                                        name="password"
                                        autoComplete="new-password"
                                        placeholder={t('New password')}
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

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-password-button"
                                    >
                                        {t('Save password')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </SettingsSection>
            </SettingsPage>
        </>
    );
}
