import { useLaravelReactI18n } from 'laravel-react-i18n';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

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
        <div className="grid gap-2">
            <Label>{t('Roles')}</Label>
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
        </div>
    );
}
