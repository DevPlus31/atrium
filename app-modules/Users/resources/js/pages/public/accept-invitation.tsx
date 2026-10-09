import { Form, Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormField } from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';

type AcceptInvitationProps = {
    email: string;
    status: 'pending' | 'accepted' | 'expired' | 'registered';
    /** The signed link itself: the form posts back to it. */
    submitUrl: string;
};

/**
 * Lives under pages/public/, so it renders without the admin shell: the
 * person opening it has no account yet.
 */
export default function AcceptInvitation({
    email,
    status,
    submitUrl,
}: AcceptInvitationProps) {
    const { t } = useLaravelReactI18n();

    if (status !== 'pending') {
        const messages = {
            accepted: t('This invitation was already used.'),
            expired: t(
                'This invitation has expired. Ask the person who invited you to send a new one.',
            ),
            registered: t('An account with this email already exists.'),
        };

        return (
            <AuthLayout
                title={t('Invitation unavailable')}
                description={messages[status]}
            >
                <Head title={t('Invitation unavailable')} />
                <p className="text-center text-sm text-muted-foreground">
                    <TextLink href={login()}>{t('Log in')}</TextLink>
                </p>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout
            title={t('Accept your invitation')}
            description={t('Choose your name and a password to finish.')}
        >
            <Head title={t('Accept your invitation')} />
            <Form
                action={submitUrl}
                method="post"
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <FormField
                            id="email"
                            label={t('Email address')}
                            error={errors.invitation}
                        >
                            <Input
                                id="email"
                                type="email"
                                value={email}
                                readOnly
                                disabled
                            />
                        </FormField>

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

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            disabled={processing}
                            data-test="accept-invitation-button"
                        >
                            {processing && <Spinner />}
                            {t('Create account')}
                        </Button>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
