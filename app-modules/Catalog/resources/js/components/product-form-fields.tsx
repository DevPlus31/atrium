import { useLaravelReactI18n } from 'laravel-react-i18n';
import { TextField } from '@/components/text-field';
import type { FormFieldsProps } from '@/types';

/**
 * The editable fields of a product, taken from the generated DTO type. The
 * form holds an empty string for "no description" (inputs need a string).
 */
export type ProductFormData = Pick<
    Modules.Catalog.Data.ProductData,
    'name' | 'sku' | 'price_cents' | 'currency'
> & { description: string };

/** The product fields shared by the create and edit forms. */
export function ProductFormFields({
    form,
    autoFocus = false,
}: FormFieldsProps<ProductFormData> & { autoFocus?: boolean }) {
    const { t } = useLaravelReactI18n();

    return (
        <>
            <TextField
                form={form}
                name="name"
                label={t('Name')}
                autoFocus={autoFocus}
                autoComplete="off"
                placeholder={t('Product name')}
            />

            <div className="grid gap-4 sm:grid-cols-2">
                <TextField
                    form={form}
                    name="sku"
                    label={t('SKU')}
                    autoComplete="off"
                    placeholder={t('e.g. WIDGET-001')}
                />

                <TextField
                    form={form}
                    name="currency"
                    type="currency"
                    label={t('Currency')}
                />
            </div>

            <TextField
                form={form}
                name="price_cents"
                type="number"
                label={t('Price (in cents)')}
            />

            <TextField
                form={form}
                name="description"
                rows={4}
                label={t('Description')}
                placeholder={t('Optional product description')}
            />
        </>
    );
}
