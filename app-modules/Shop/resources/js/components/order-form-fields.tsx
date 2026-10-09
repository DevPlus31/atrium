import { useLaravelReactI18n } from 'laravel-react-i18n';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/** The editable fields of an order, taken from the generated DTO type. */
export type OrderFormData = Pick<
    Modules.Shop.Data.OrderData,
    'customer_email' | 'total_cents' | 'currency'
>;

type OrderField = keyof OrderFormData;

type OrderFormFieldsProps = {
    data: OrderFormData;
    errors: Partial<Record<OrderField, string>>;
    setData: <K extends OrderField>(field: K, value: OrderFormData[K]) => void;
    validate: (field: OrderField) => void;
};

/** The customer and total fields shared by the create and edit forms. */
export function OrderFormFields({
    data,
    errors,
    setData,
    validate,
}: OrderFormFieldsProps) {
    const { t } = useLaravelReactI18n();

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="customer_email">{t('Customer email')}</Label>
                <Input
                    id="customer_email"
                    type="email"
                    autoComplete="off"
                    value={data.customer_email}
                    onChange={(event) =>
                        setData('customer_email', event.target.value)
                    }
                    onBlur={() => validate('customer_email')}
                />
                <InputError message={errors.customer_email} />
            </div>
            <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                <div className="grid gap-2">
                    <Label htmlFor="total_cents">{t('Total (in cents)')}</Label>
                    <Input
                        id="total_cents"
                        type="number"
                        value={data.total_cents}
                        onChange={(event) =>
                            setData('total_cents', Number(event.target.value))
                        }
                        onBlur={() => validate('total_cents')}
                    />
                    <InputError message={errors.total_cents} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="currency">{t('Currency')}</Label>
                    <Input
                        id="currency"
                        type="text"
                        value={data.currency}
                        onChange={(event) =>
                            setData('currency', event.target.value)
                        }
                        onBlur={() => validate('currency')}
                    />
                    <InputError message={errors.currency} />
                </div>
            </div>
        </>
    );
}
