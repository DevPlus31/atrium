import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

type RolePermissionsFieldProps = {
    permissions: string[];
    selected: string[];
    onChange: (permissions: string[]) => void;
    error?: string;
};

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

export function RolePermissionsField({
    permissions,
    selected,
    onChange,
    error,
}: RolePermissionsFieldProps) {
    const groups = groupByModule(permissions);

    const toggle = (permission: string, checked: boolean) => {
        onChange(
            checked
                ? [...selected, permission]
                : selected.filter((value) => value !== permission),
        );
    };

    const toggleGroup = (entries: string[], checked: boolean) => {
        onChange(
            checked
                ? [
                      ...selected,
                      ...entries.filter((entry) => !selected.includes(entry)),
                  ]
                : selected.filter((value) => !entries.includes(value)),
        );
    };

    return (
        <div className="grid gap-2">
            <Label>Permissions</Label>
            {permissions.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No permissions available.
                </p>
            ) : (
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
                                            toggleGroup(
                                                entries,
                                                checked === true,
                                            )
                                        }
                                        aria-label={`Select all ${prefix} permissions`}
                                    />
                                    {prefix}
                                </label>
                                <div className="grid gap-2 ps-6">
                                    {entries.map((permission) => (
                                        <label
                                            key={permission}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={selected.includes(
                                                    permission,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggle(
                                                        permission,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            {permission}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
            <InputError message={error} />
        </div>
    );
}
