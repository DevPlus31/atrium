import { Head, InfiniteScroll, router, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { MailPlus } from 'lucide-react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import { index as usersIndex } from '@/routes/admin/users';
import {
    destroy,
    index,
    resend,
    store,
} from '@/routes/admin/users/invitations';
import type { BreadcrumbItem } from '@/types';
import { UserRolesField } from '../components/user-roles-field';

type Invitation = Modules.Users.Data.InvitationData;

type InvitationsProps = {
    invitations: { data: Invitation[] };
    roles: string[];
    lifetimeDays: number;
};

export default function Invitations({
    invitations,
    roles,
    lifetimeDays,
}: InvitationsProps) {
    const { t } = useLaravelReactI18n();
    const { date } = useFormatters();
    const revokeDialog = useDeleteDialog<Invitation>((invitation) =>
        destroy.url(invitation.id),
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Users'), href: usersIndex() },
        { title: t('Invitations'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm(store(), { email: '', roles: [] as string[] });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <>
            <Head title={t('Invitations')} />
            <div className="grid max-w-2xl gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Invite someone')}</CardTitle>
                        <CardDescription>
                            {t(
                                'They get an email with a link to create their account. Links work for :days days, even when sign-ups are closed.',
                                { days: String(lifetimeDays) },
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('Email address')}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="off"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                    onBlur={() => form.validate('email')}
                                    placeholder="email@example.com"
                                />
                                <InputError message={form.errors.email} />
                            </div>

                            <UserRolesField
                                roles={roles}
                                selected={form.data.roles}
                                onChange={(next) => {
                                    form.setData('roles', next);
                                    form.validate('roles');
                                }}
                                error={form.errors.roles}
                            />

                            <div>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    data-test="send-invitation"
                                >
                                    {form.processing ? (
                                        <Spinner />
                                    ) : (
                                        <MailPlus className="size-4" />
                                    )}
                                    {t('Send invitation')}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Pending invitations')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {invitations.data.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                {t('No pending invitations.')}
                            </p>
                        ) : (
                            <InfiniteScroll
                                data="invitations"
                                as="ul"
                                className="divide-y"
                            >
                                {invitations.data.map((invitation) => (
                                    <li
                                        key={invitation.id}
                                        className="flex flex-wrap items-center gap-3 py-3"
                                        data-test="invitation"
                                    >
                                        <div className="grid min-w-0 flex-1 gap-1">
                                            <span className="truncate text-sm font-medium">
                                                {invitation.email}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {invitation.invited_by
                                                    ? t(
                                                          'Invited by :name · expires :date',
                                                          {
                                                              name: invitation.invited_by,
                                                              date: date(
                                                                  invitation.expires_at,
                                                              ),
                                                          },
                                                      )
                                                    : t('Expires :date', {
                                                          date: date(
                                                              invitation.expires_at,
                                                          ),
                                                      })}
                                            </span>
                                            {invitation.roles.length > 0 && (
                                                <span className="flex flex-wrap gap-1">
                                                    {invitation.roles.map(
                                                        (role) => (
                                                            <Badge
                                                                key={role}
                                                                variant="secondary"
                                                            >
                                                                {role}
                                                            </Badge>
                                                        ),
                                                    )}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.post(
                                                        resend.url(
                                                            invitation.id,
                                                        ),
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {t('Resend')}
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() =>
                                                    revokeDialog.request(
                                                        invitation,
                                                    )
                                                }
                                            >
                                                {t('Revoke')}
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </InfiniteScroll>
                        )}
                    </CardContent>
                </Card>
            </div>
            <ConfirmDialog
                {...revokeDialog.dialogProps}
                title={t('Revoke invitation')}
                description={t('The link sent to :email will stop working.', {
                    email: revokeDialog.pending?.email ?? '',
                })}
                confirmLabel={t('Revoke')}
            />
        </>
    );
}
