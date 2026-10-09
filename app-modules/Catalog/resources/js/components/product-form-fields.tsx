import { useLaravelReactI18n } from 'laravel-react-i18n';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

/**
 * The editable fields of a product, taken from the generated DTO type. The
 * form holds an empty string for "no description" (inputs need a string).
 */
export type ProductFormData = Pick<
    Modules.Catalog.Data.ProductData,
    'name' | 'sku' | 'price_cents' | 'currency'
> & { description: string };

type ProductField = keyof ProductFormData;

type ProductFormFieldsProps = {
    data: ProductFormData;
    errors: Partial<Record<ProductField, string>>;
    setData: <K extends ProductField>(
        field: K,
        value: ProductFormData[K],
    ) => void;
    validate: (field: ProductField) => void;
    autoFocus?: boolean;
};

/** The product fields shared by the create and edit forms. */
export function ProductFormFields({
    data,
    errors,
    setData,
    validate,
    autoFocus = false,
}: ProductFormFieldsProps) {
    const { t } = useLaravelReactI18n();

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">{t('Name')}</Label>
                <Input
                    id="name"
                    type="text"
                    autoFocus={autoFocus}
                    autoComplete="off"
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    onBlur={() => validate('name')}
                    placeholder={t('Product name')}
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="sku">{t('SKU')}</Label>
                    <Input
                        id="sku"
                        type="text"
                        autoComplete="off"
                        value={data.sku}
                        onChange={(event) => setData('sku', event.target.value)}
                        onBlur={() => validate('sku')}
                        placeholder={t('e.g. WIDGET-001')}
                    />
                    <InputError message={errors.sku} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="currency">{t('Currency')}</Label>
                    <Input
                        id="currency"
                        type="text"
                        autoComplete="off"
                        value={data.currency}
                        onChange={(event) =>
                            setData(
                                'currency',
                                event.target.value.toUpperCase(),
                            )
                        }
                        onBlur={() => validate('currency')}
                        placeholder="USD"
                    />
                    <InputError message={errors.currency} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="price_cents">{t('Price (in cents)')}</Label>
                <Input
                    id="price_cents"
                    type="number"
                    value={data.price_cents}
                    onChange={(event) =>
                        setData('price_cents', Number(event.target.value))
                    }
                    onBlur={() => validate('price_cents')}
                />
                <InputError message={errors.price_cents} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">{t('Description')}</Label>
                <Textarea
                    id="description"
                    rows={4}
                    value={data.description}
                    onChange={(event) =>
                        setData('description', event.target.value)
                    }
                    onBlur={() => validate('description')}
                    placeholder={t('Optional product description')}
                />
                <InputError message={errors.description} />
            </div>
        </>
    );
}
