import { Form, Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import UserAvatarController from '@/actions/App/Http/Controllers/UserAvatarController';
import UserProfileController from '@/actions/App/Http/Controllers/UserProfileController';
import DeleteUser from '@/components/delete-user';
import { FormField } from '@/components/form-field';
import { ImageInput } from '@/components/image-input';
import PasswordInput from '@/components/password-input';
import { SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import { SettingsPage } from '@/layouts/settings/page';
import { edit } from '@/routes/user-profile';
import { send } from '@/routes/verification';

export default function Edit({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { t } = useLaravelReactI18n();
    const { auth } = usePage().props;
    const [emailChanged, setEmailChanged] = useState(false);
    const getInitials = useInitials();

    return (
        <>
            <SettingsPage title={t('Profile settings')} href={edit()}>
                <SettingsSection
                    title={t('Profile information')}
                    description={t('Update your name and email address')}
                >
                    <div className="grid gap-2">
                        <Label>{t('Photo')}</Label>
                        <ImageInput
                            label={t('Profile photo')}
                            imageUrl={auth.user.avatar}
                            fallback={
                                <span className="text-lg font-medium">
                                    {getInitials(auth.user.name)}
                                </span>
                            }
                            field="avatar"
                            uploadUrl={UserAvatarController.update.url()}
                            removeUrl={UserAvatarController.destroy.url()}
                        />
                    </div>

                    <Form
                        {...UserProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <FormField
                                    id="name"
                                    label={t('Name')}
                                    error={errors.name}
                                >
                                    <Input
                                        id="name"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
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
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder={t('Email address')}
                                        onChange={(event) =>
                                            setEmailChanged(
                                                event.target.value !==
                                                    auth.user.email,
                                            )
                                        }
                                    />
                                </FormField>

                                {emailChanged && (
                                    <FormField
                                        id="current_password"
                                        label={t('Current password')}
                                        error={errors.current_password}
                                        hint={t(
                                            'Confirm your password to change your email address.',
                                        )}
                                    >
                                        <PasswordInput
                                            id="current_password"
                                            name="current_password"
                                            required
                                            autoComplete="current-password"
                                            placeholder={t('Current password')}
                                        />
                                    </FormField>
                                )}

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div>
                                            <p className="-mt-4 text-sm text-muted-foreground">
                                                {t(
                                                    'Your email address is unverified.',
                                                )}{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="text-foreground underline decoration-muted-foreground/50 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                                                >
                                                    {t(
                                                        'Click here to resend the verification email.',
                                                    )}
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-success">
                                                    {t(
                                                        'A new verification link has been sent to your email address.',
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        {t('Save')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </SettingsSection>

                <DeleteUser />
            </SettingsPage>
        </>
    );
}
