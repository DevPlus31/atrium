import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { TextField } from '@/components/text-field';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { create, index, store } from '@/routes/admin/roles';
import { RolePermissionsField } from '../components/role-permissions-field';

type RolesCreateProps = {
    permissions: string[];
};

export default function RolesCreate({ permissions }: RolesCreateProps) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs(
        { title: t('Roles'), href: index() },
        { title: t('Create'), href: create() },
    );

    const form = useForm(store(), {
        name: '',
        permissions: [] as string[],
    });

    return (
        <>
            <Head title={t('Create role')} />
            <FormCard
                title={t('Create role')}
                description={t('Add a new role and choose its permissions.')}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Create role')}
                cancelHref={index()}
            >
                <TextField
                    form={form}
                    name="name"
                    label={t('Name')}
                    autoFocus
                    autoComplete="off"
                    placeholder={t('Role name')}
                />

                <RolePermissionsField form={form} permissions={permissions} />
            </FormCard>
        </>
    );
}
