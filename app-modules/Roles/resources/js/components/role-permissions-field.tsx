import { useLaravelReactI18n } from 'laravel-react-i18n';
import { CheckboxGroupField, CheckboxList } from '@/components/checkbox-group';
import { Checkbox } from '@/components/ui/checkbox';
import { toggleValues } from '@/lib/selection';
import type { FormFieldsProps } from '@/types';

function groupByModule(permissions: string[]): Map<string, string[]> {
    const groups = new Map<string, string[]>();

    for (const permission of permissions) {
        const dotIndex = permission.indexOf('.');
        const prefix =
            dotIndex === -1 ? permission : permission.slice(0, dotIndex);
        const entries = groups.get(prefix);

        if (entries) {
            entries.push(permission);
        } else {
            groups.set(prefix, [permission]);
        }
    }

    return groups;
}

/** The role's permissions, grouped by module, bound to the form's `permissions` field. */
export function RolePermissionsField({
    form,
    permissions,
}: FormFieldsProps<{ permissions: string[] }> & { permissions: string[] }) {
    const { t } = useLaravelReactI18n();
    const groups = groupByModule(permissions);
    const selected = form.data.permissions;
    const onChange = (next: string[]) => {
        form.setData('permissions', next);
        form.validate?.('permissions');
    };

    return (
        <CheckboxGroupField
            legend={t('Permissions')}
            isEmpty={permissions.length === 0}
            emptyMessage={t('No permissions available.')}
            error={form.errors.permissions}
        >
            <div className="grid gap-4">
                {[...groups.entries()].map(([prefix, entries]) => {
                    const selectedCount = entries.filter((entry) =>
                        selected.includes(entry),
                    ).length;

                    return (
                        <div key={prefix} className="grid gap-2">
                            <label className="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                <Checkbox
                                    checked={
                                        selectedCount === entries.length
                                            ? true
                                            : selectedCount > 0
                                              ? 'indeterminate'
                                              : false
                                    }
                                    onCheckedChange={(checked) =>
                                        onChange(
                                            toggleValues(
                                                selected,
                                                entries,
                                                checked === true,
                                            ),
                                        )
                                    }
                                    aria-label={t(
                                        'Select all :prefix permissions',
                                        { prefix },
                                    )}
                                />
                                {prefix}
                            </label>
                            <CheckboxList
                                className="ps-6"
                                options={entries}
                                selected={selected}
                                onChange={onChange}
                            />
                        </div>
                    );
                })}
            </div>
        </CheckboxGroupField>
    );
}
