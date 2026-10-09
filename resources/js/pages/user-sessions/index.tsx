import { Form, Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Monitor, Smartphone } from 'lucide-react';
import { useRef } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useFormatters } from '@/hooks/use-formatters';
import SettingsLayout from '@/layouts/settings/layout';
import { destroy, index } from '@/routes/sessions';
import type { BreadcrumbItem } from '@/types';

type Session = App.Modules.Data.SessionData;

type SessionsProps = {
    /** False when sessions are not stored in the database. */
    listable: boolean;
    sessions: Session[];
};

export default function Sessions({ listable, sessions }: SessionsProps) {
    const { t } = useLaravelReactI18n();
    const { dateTime } = useFormatters();
    const passwordInput = useRef<HTMLInputElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Sessions'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    const deviceName = (session: Session): string => {
        if (session.browser && session.platform) {
            return t(':browser on :platform', {
                browser: session.browser,
                platform: session.platform,
            });
        }

        return session.browser ?? session.platform ?? t('Unknown device');
    };

    return (
        <>
            <Head title={t('Sessions')} />

            <h1 className="sr-only">{t('Sessions')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('Browser sessions')}
                        description={t(
                            'Where you are signed in. If you see a device you don’t recognize, sign out of your other sessions and change your password.',
                        )}
                    />

                    {listable && (
                        <ul className="space-y-4">
                            {sessions.map((session, position) => {
                                const DeviceIcon = session.is_mobile
                                    ? Smartphone
                                    : Monitor;

                                return (
                                    <li
                                        key={`${session.last_active}-${position}`}
                                        className="flex items-center gap-3"
                                        data-test="session"
                                    >
                                        <DeviceIcon
                                            className="size-6 shrink-0 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <div className="grid min-w-0 gap-0.5">
                                            <span className="truncate text-sm font-medium">
                                                {deviceName(session)}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {[
                                                    session.ip_address,
                                                    session.is_current
                                                        ? null
                                                        : t(
                                                              'Last active :date',
                                                              {
                                                                  date: dateTime(
                                                                      session.last_active,
                                                                  ),
                                                              },
                                                          ),
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </span>
                                        </div>
                                        {session.is_current && (
                                            <Badge
                                                variant="secondary"
                                                className="ms-auto"
                                            >
                                                {t('This device')}
                                            </Badge>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    )}

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="outline"
                                data-test="sign-out-other-sessions"
                            >
                                {t('Sign out other sessions')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>
                                {t('Sign out other sessions')}
                            </DialogTitle>
                            <DialogDescription>
                                {t(
                                    'Enter your password to sign out of every other browser and device, including ones that chose “Remember me”.',
                                )}
                            </DialogDescription>

                            <Form
                                {...destroy.form()}
                                options={{ preserveScroll: true }}
                                onError={() => passwordInput.current?.focus()}
                                resetOnSuccess
                                className="space-y-6"
                            >
                                {({
                                    resetAndClearErrors,
                                    processing,
                                    errors,
                                }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="session_password"
                                                className="sr-only"
                                            >
                                                {t('Password')}
                                            </Label>
                                            <PasswordInput
                                                id="session_password"
                                                name="password"
                                                ref={passwordInput}
                                                placeholder={t('Password')}
                                                autoComplete="current-password"
                                            />
                                            <InputError
                                                message={errors.password}
                                            />
                                        </div>

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button
                                                    variant="secondary"
                                                    onClick={() =>
                                                        resetAndClearErrors()
                                                    }
                                                >
                                                    {t('Cancel')}
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                data-test="confirm-sign-out-other-sessions"
                                            >
                                                {t('Sign out other sessions')}
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </SettingsLayout>
        </>
    );
}
