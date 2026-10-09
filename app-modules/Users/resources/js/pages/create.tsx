import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { TextField } from '@/components/text-field';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { create, index, store } from '@/routes/admin/users';
import { UserAccountFields } from '../components/user-account-fields';
import { UserRolesField } from '../components/user-roles-field';

type UsersCreateProps = {
    roles: string[];
};

export default function UsersCreate({ roles }: UsersCreateProps) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs(
        { title: t('Users'), href: index() },
        { title: t('Create'), href: create() },
    );

    const form = useForm(store(), {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [] as string[],
    });

    return (
        <>
            <Head title={t('Create user')} />
            <FormCard
                title={t('Create user')}
                description={t('Add a new user and assign their roles.')}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Create user')}
                cancelHref={index()}
            >
                <UserAccountFields form={form} autoFocus />

                <TextField
                    form={form}
                    name="password"
                    type="password"
                    label={t('Password')}
                    autoComplete="new-password"
                    placeholder={t('Password')}
                />

                <TextField
                    form={form}
                    name="password_confirmation"
                    type="password"
                    validates="password"
                    label={t('Confirm password')}
                    autoComplete="new-password"
                    placeholder={t('Confirm password')}
                />

                <UserRolesField form={form} roles={roles} />
            </FormCard>
        </>
    );
}
