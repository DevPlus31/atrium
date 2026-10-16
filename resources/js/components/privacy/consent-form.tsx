import { useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { CheckboxField } from '@/components/checkbox-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useConsent } from '@/hooks/use-consent';
import { toggleValue } from '@/lib/selection';
import { handleSubmit } from '@/lib/utils';
import { store } from '@/routes/consent';

type ConsentFormProps = {
    /** Called once the choice is saved (e.g. to close a dialog). */
    onSaved?: () => void;
};

/**
 * The cookie preference form: the strictly necessary cookies (always on),
 * then one checkbox per optional category. Used by the consent banner's
 * "Customize" dialog, the Cookie Policy page and Settings → Privacy.
 */
export function ConsentForm({ onSaved }: ConsentFormProps) {
    const { t } = useLaravelReactI18n();
    const consent = useConsent();
    const form = useForm(store(), { granted: consent.granted });

    const blockedByGpc = (category: App.Modules.Data.ConsentCategoryData) =>
        consent.gpc && category.sale_or_sharing;

    return (
        <form
            onSubmit={handleSubmit(() =>
                form.submit({ preserveScroll: true, onSuccess: onSaved }),
            )}
            className="space-y-6"
            data-test="consent-form"
        >
            <div className="flex items-start gap-3">
                <Checkbox id="consent-necessary" checked disabled />
                <div className="grid gap-1">
                    <Label htmlFor="consent-necessary">
                        {t('Strictly necessary')}
                    </Label>
                    <p className="text-sm text-muted-foreground">
                        {t(
                            'Keep you signed in, protect forms and remember your display choices. Always on.',
                        )}
                    </p>
                </div>
            </div>

            {consent.categories.map((category) => (
                <CheckboxField
                    key={category.value}
                    id={`consent-${category.value}`}
                    label={category.label}
                    hint={
                        blockedByGpc(category)
                            ? `${category.description} ${t('Off: your browser sends a Global Privacy Control signal.')}`
                            : category.description
                    }
                    checked={
                        !blockedByGpc(category) &&
                        form.data.granted.includes(category.value)
                    }
                    disabled={blockedByGpc(category)}
                    onCheckedChange={(checked) =>
                        form.setData(
                            'granted',
                            toggleValue(
                                form.data.granted,
                                category.value,
                                checked,
                            ),
                        )
                    }
                />
            ))}

            <Button
                type="submit"
                disabled={form.processing}
                data-test="save-consent"
            >
                {form.processing && <Spinner />}
                {t('Save my choices')}
            </Button>
        </form>
    );
}
