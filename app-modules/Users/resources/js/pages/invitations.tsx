import { Head, InfiniteScroll, router, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { MailPlus } from 'lucide-react';
import { BadgeList } from '@/components/badge-list';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { FormCard } from '@/components/form-card';
import { itemListClassName, ItemRow } from '@/components/item-list';
import { PageStack, SectionCard } from '@/components/section-card';
import { TextField } from '@/components/text-field';
import { Button } from '@/components/ui/button';
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

    useBreadcrumbs(
        { title: t('Users'), href: usersIndex() },
        { title: t('Invitations'), href: index() },
    );

    const form = useForm(store(), { email: '', roles: [] as string[] });

    return (
        <>
            <Head title={t('Invitations')} />
            <PageStack>
                <FormCard
                    title={t('Invite someone')}
                    description={t(
                        'They get an email with a link to create their account. Links work for :days days, even when sign-ups are closed.',
                        { days: String(lifetimeDays) },
                    )}
                    onSubmit={() =>
                        form.submit({
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                        })
                    }
                    processing={form.processing}
                    submitLabel={t('Send invitation')}
                    submitIcon={<MailPlus className="size-4" />}
                    submitTest="send-invitation"
                >
                    <TextField
                        form={form}
                        name="email"
                        type="email"
                        label={t('Email address')}
                        autoComplete="off"
                        placeholder={t('email@example.com')}
                    />

                    <UserRolesField form={form} roles={roles} />
                </FormCard>

                <SectionCard title={t('Pending invitations')}>
                    {invitations.data.length === 0 ? (
                        <EmptyState centered>
                            {t('No pending invitations.')}
                        </EmptyState>
                    ) : (
                        <InfiniteScroll
                            data="invitations"
                            as="ul"
                            className={itemListClassName}
                        >
                            {invitations.data.map((invitation) => (
                                <ItemRow
                                    key={invitation.id}
                                    test="invitation"
                                    title={invitation.email}
                                    details={
                                        invitation.invited_by
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
                                              })
                                    }
                                    extra={
                                        <BadgeList
                                            items={invitation.roles}
                                            empty={null}
                                        />
                                    }
                                    actions={
                                        <>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                aria-label={t(
                                                    'Resend the invitation to :email',
                                                    {
                                                        email: invitation.email,
                                                    },
                                                )}
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
                                                aria-label={t(
                                                    'Revoke the invitation to :email',
                                                    {
                                                        email: invitation.email,
                                                    },
                                                )}
                                                onClick={() =>
                                                    revokeDialog.request(
                                                        invitation,
                                                    )
                                                }
                                            >
                                                {t('Revoke')}
                                            </Button>
                                        </>
                                    }
                                />
                            ))}
                        </InfiniteScroll>
                    )}
                </SectionCard>
            </PageStack>
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
