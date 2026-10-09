import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { TextField } from '@/components/text-field';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, index, update } from '@/routes/admin/roles';
import type { RoleRow } from '../components/role-columns';
import { RolePermissionsField } from '../components/role-permissions-field';

type RolesEditProps = {
    role: RoleRow;
    permissions: string[];
};

export default function RolesEdit({ role, permissions }: RolesEditProps) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs(
        { title: t('Roles'), href: index() },
        { title: role.name, href: edit(Number(role.id)) },
    );

    const form = useForm(update(Number(role.id)), {
        name: role.name,
        permissions: role.permissions,
    });

    return (
        <>
            <Head title={t('Edit :name', { name: role.name })} />
            <FormCard
                title={t('Edit role')}
                description={t("Update the role's name and permissions.")}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Save changes')}
                cancelHref={index()}
            >
                <TextField
                    form={form}
                    name="name"
                    label={t('Name')}
                    hint={
                        role.is_system
                            ? t('System role names cannot be changed.')
                            : undefined
                    }
                    autoComplete="off"
                    disabled={role.is_system}
                    placeholder={t('Role name')}
                />

                <RolePermissionsField form={form} permissions={permissions} />
            </FormCard>
        </>
    );
}
