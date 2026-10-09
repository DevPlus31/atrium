import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import UserAvatarController from '@/actions/App/Http/Controllers/UserAvatarController';
import UserProfileController from '@/actions/App/Http/Controllers/UserProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import { ImageInput } from '@/components/image-input';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useInitials } from '@/hooks/use-initials';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/user-profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

export default function Edit({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { t } = useLaravelReactI18n();
    const { auth, errors } = usePage().props;
    const [emailChanged, setEmailChanged] = useState(false);
    const [uploading, setUploading] = useState(false);
    const getInitials = useInitials();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('Profile settings'),
            href: edit(),
        },
    ];
    useBreadcrumbs(breadcrumbs);

    return (
        <>
            <Head title={t('Profile settings')} />

            <h1 className="sr-only">{t('Profile settings')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('Profile information')}
                        description={t('Update your name and email address')}
                    />

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
                            error={errors.avatar}
                            processing={uploading}
                            onSelect={(file) =>
                                router.post(
                                    UserAvatarController.update.url(),
                                    { avatar: file },
                                    {
                                        forceFormData: true,
                                        preserveScroll: true,
                                        onStart: () => setUploading(true),
                                        onFinish: () => setUploading(false),
                                    },
                                )
                            }
                            onRemove={() =>
                                router.delete(
                                    UserAvatarController.destroy.url(),
                                    { preserveScroll: true },
                                )
                            }
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
                                <div className="grid gap-2">
                                    <Label htmlFor="name">{t('Name')}</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder={t('Full name')}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        {t('Email address')}
                                    </Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
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

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                {emailChanged && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="current_password">
                                            {t('Current password')}
                                        </Label>

                                        <PasswordInput
                                            id="current_password"
                                            name="current_password"
                                            className="mt-1 block w-full"
                                            required
                                            autoComplete="current-password"
                                            placeholder={t('Current password')}
                                        />

                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'Confirm your password to change your email address.',
                                            )}
                                        </p>

                                        <InputError
                                            className="mt-2"
                                            message={errors.current_password}
                                        />
                                    </div>
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
                </div>

                <DeleteUser />
            </SettingsLayout>
        </>
    );
}
