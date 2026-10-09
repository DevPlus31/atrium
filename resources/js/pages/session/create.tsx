import { Form, Head, router, usePage } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { KeyRound } from 'lucide-react';
import { useState } from 'react';
import { AuthStatus } from '@/components/auth/auth-status';
import { AuthSubmit } from '@/components/auth/auth-submit';
import { CheckboxField } from '@/components/checkbox-field';
import { FormField } from '@/components/form-field';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { dashboard, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
};

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: Props) {
    const { t } = useLaravelReactI18n();
    const { name } = usePage().props;
    // Controlled so passkey sign-in honours "Remember me" too.
    const [remember, setRemember] = useState(false);
    const {
        verify,
        isLoading: verifyingPasskey,
        error: passkeyError,
        isSupported: passkeysSupported,
    } = usePasskeyVerify({
        remember: () => remember,
        onSuccess: (response) => {
            router.visit(response.redirect ?? dashboard().url);
        },
    });

    return (
        <AuthLayout
            title={t('Welcome back')}
            description={t('Log in to continue to your workspace.')}
        >
            <Head title={t('Log in')} />

            {status && <AuthStatus>{status}</AuthStatus>}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <FormField
                                id="email"
                                label={t('Email address')}
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    placeholder={t('email@example.com')}
                                />
                            </FormField>

                            <FormField
                                id="password"
                                label={t('Password')}
                                error={errors.password}
                                labelAside={
                                    canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="text-sm"
                                        >
                                            {t('Forgot password?')}
                                        </TextLink>
                                    )
                                }
                            >
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder={t('Password')}
                                />
                            </FormField>

                            <CheckboxField
                                id="remember"
                                name="remember"
                                label={t('Remember me')}
                                checked={remember}
                                onCheckedChange={setRemember}
                            />

                            <AuthSubmit
                                processing={processing}
                                test="login-button"
                                className="mt-2"
                            >
                                {t('Log in')}
                            </AuthSubmit>

                            {passkeysSupported && (
                                <div className="grid gap-2">
                                    <div
                                        aria-hidden
                                        className="flex items-center gap-3 text-xs text-muted-foreground uppercase"
                                    >
                                        <span className="h-px flex-1 bg-border" />
                                        {t('or')}
                                        <span className="h-px flex-1 bg-border" />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full"
                                        disabled={verifyingPasskey}
                                        data-test="passkey-login-button"
                                        onClick={() => {
                                            void verify();
                                        }}
                                    >
                                        {verifyingPasskey ? (
                                            <Spinner />
                                        ) : (
                                            <KeyRound />
                                        )}
                                        {t('Sign in with a passkey')}
                                    </Button>
                                    <InputError
                                        message={passkeyError ?? undefined}
                                    />
                                </div>
                            )}
                        </div>

                        {canRegister && (
                            <p className="text-sm text-muted-foreground">
                                {t('New to :name?', { name })}{' '}
                                <TextLink href={register()}>
                                    {t('Create an account')}
                                </TextLink>
                            </p>
                        )}
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
