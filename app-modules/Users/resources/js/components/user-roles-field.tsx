import { useLaravelReactI18n } from 'laravel-react-i18n';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';

type UserRolesFieldProps = {
    roles: string[];
    selected: string[];
    onChange: (roles: string[]) => void;
    error?: string;
};

export function UserRolesField({
    roles,
    selected,
    onChange,
    error,
}: UserRolesFieldProps) {
    const { t } = useLaravelReactI18n();

    const toggle = (role: string, checked: boolean) => {
        onChange(
            checked
                ? [...selected, role]
                : selected.filter((value) => value !== role),
        );
    };

    return (
        // A group of checkboxes: the legend names the group for screen readers.
        <fieldset className="grid gap-2">
            <legend className="mb-2 text-sm leading-none font-medium">
                {t('Roles')}
            </legend>
            {roles.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('No roles available.')}
                </p>
            ) : (
                <div className="grid gap-2">
                    {roles.map((role) => (
                        <label
                            key={role}
                            className="flex items-center gap-2 text-sm"
                        >
                            <Checkbox
                                checked={selected.includes(role)}
                                onCheckedChange={(checked) =>
                                    toggle(role, checked === true)
                                }
                            />
                            {role}
                        </label>
                    ))}
                </div>
            )}
            <InputError message={error} />
        </fieldset>
    );
}
