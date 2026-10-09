import { useLaravelReactI18n } from 'laravel-react-i18n';
import { TextField } from '@/components/text-field';
import type { FormFieldsProps } from '@/types';

/** The name and email fields shared by the create and edit forms. */
export function UserAccountFields({
    form,
    autoFocus = false,
}: FormFieldsProps<{ name: string; email: string }> & {
    autoFocus?: boolean;
}) {
    const { t } = useLaravelReactI18n();

    return (
        <>
            <TextField
                form={form}
                name="name"
                label={t('Name')}
                autoFocus={autoFocus}
                autoComplete="off"
                placeholder={t('Full name')}
            />

            <TextField
                form={form}
                name="email"
                type="email"
                label={t('Email address')}
                autoComplete="off"
                placeholder={t('email@example.com')}
            />
        </>
    );
}
