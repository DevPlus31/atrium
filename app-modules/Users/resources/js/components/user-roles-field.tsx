import { useLaravelReactI18n } from 'laravel-react-i18n';
import { CheckboxGroupField, CheckboxList } from '@/components/checkbox-group';
import type { FormFieldsProps } from '@/types';

/** The roles to grant, bound to the form's `roles` field. */
export function UserRolesField({
    form,
    roles,
}: FormFieldsProps<{ roles: string[] }> & { roles: string[] }) {
    const { t } = useLaravelReactI18n();

    return (
        <CheckboxGroupField
            legend={t('Roles')}
            isEmpty={roles.length === 0}
            emptyMessage={t('No roles available.')}
            error={form.errors.roles}
        >
            <CheckboxList
                options={roles}
                selected={form.data.roles}
                onChange={(next) => {
                    form.setData('roles', next);
                    form.validate?.('roles');
                }}
            />
        </CheckboxGroupField>
    );
}
