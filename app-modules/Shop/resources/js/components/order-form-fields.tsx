import { useLaravelReactI18n } from 'laravel-react-i18n';
import { TextField } from '@/components/text-field';
import type { FormFieldsProps } from '@/types';

/** The editable fields of an order, taken from the generated DTO type. */
export type OrderFormData = Pick<
    Modules.Shop.Data.OrderData,
    'customer_email' | 'total_cents' | 'currency'
>;

/** The customer and total fields shared by the create and edit forms. */
export function OrderFormFields({ form }: FormFieldsProps<OrderFormData>) {
    const { t } = useLaravelReactI18n();

    return (
        <>
            <TextField
                form={form}
                name="customer_email"
                type="email"
                label={t('Customer email')}
                autoComplete="off"
            />
            <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                <TextField
                    form={form}
                    name="total_cents"
                    type="number"
                    label={t('Total (in cents)')}
                />
                <TextField
                    form={form}
                    name="currency"
                    type="currency"
                    label={t('Currency')}
                />
            </div>
        </>
    );
}
