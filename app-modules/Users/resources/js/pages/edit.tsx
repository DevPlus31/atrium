import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, index, update } from '@/routes/admin/users';
import { UserAccountFields } from '../components/user-account-fields';
import type { UserRow } from '../components/user-columns';
import { UserRolesField } from '../components/user-roles-field';

type UsersEditProps = {
    user: UserRow;
    roles: string[];
};

export default function UsersEdit({ user, roles }: UsersEditProps) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs(
        { title: t('Users'), href: index() },
        { title: user.name, href: edit(user.id) },
    );

    const form = useForm(update(user.id), {
        name: user.name,
        email: user.email,
        roles: user.roles,
    });

    return (
        <>
            <Head title={t('Edit :name', { name: user.name })} />
            <FormCard
                title={t('Edit user')}
                description={t(
                    "Update the user's details and roles. Changing the email address resets its verification.",
                )}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Save changes')}
                cancelHref={index()}
            >
                <UserAccountFields form={form} />

                <UserRolesField form={form} roles={roles} />
            </FormCard>
        </>
    );
}
