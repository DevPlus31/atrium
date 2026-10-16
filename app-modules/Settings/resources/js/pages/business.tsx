import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useMemo } from 'react';
import { ComboboxField } from '@/components/combobox-field';
import type { ComboboxOption } from '@/components/combobox-field';
import { FormCard } from '@/components/form-card';
import { PageStack, SectionCard } from '@/components/section-card';
import { SelectField } from '@/components/select-field';
import { TextField } from '@/components/text-field';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { createFormatters } from '@/lib/format';
import { edit, update } from '@/routes/admin/settings/business';

type BusinessSettingsProps = {
    settings: App.Modules.Data.BusinessData;
};

/** Every option the runtime knows, named in the viewer's language. */
function useIntlOptions(
    kind: 'currency' | 'timeZone',
    locale: string,
): ComboboxOption[] {
    return useMemo(() => {
        if (kind === 'timeZone') {
            return Intl.supportedValuesOf('timeZone').map((zone) => ({
                value: zone,
                label: zone,
            }));
        }

        const names = new Intl.DisplayNames([locale], { type: 'currency' });

        return Intl.supportedValuesOf('currency').map((code) => {
            const name = names.of(code) ?? code;

            return {
                value: code,
                label: `${code} · ${name}`,
                keywords: [name],
            };
        });
    }, [kind, locale]);
}

/** Whether the runtime can format with this regional format tag. */
const isValidLocale = (tag: string): boolean => {
    try {
        return Intl.NumberFormat.supportedLocalesOf([tag]).length > 0;
    } catch {
        return false;
    }
};

export default function BusinessSettings({ settings }: BusinessSettingsProps) {
    const { t, currentLocale } = useLaravelReactI18n();

    useBreadcrumbs({ title: t('Business settings'), href: edit() });

    const form = useForm(update(), {
        timezone: settings.timezone,
        locale: settings.locale,
        currency: settings.currency,
        distance_unit: settings.distance_unit,
    });

    const timeZones = useIntlOptions('timeZone', currentLocale());
    const currencies = useIntlOptions('currency', currentLocale());

    // The figures as they will read once saved.
    const preview = isValidLocale(form.data.locale)
        ? createFormatters(currentLocale(), form.data.timezone, form.data)
        : null;

    return (
        <>
            <Head title={t('Business settings')} />
            <PageStack>
                <FormCard
                    title={t('Business settings')}
                    description={t(
                        'How the business works. Everyone reads dates in their own language and time zone; amounts, percentages and distances read the same for everyone.',
                    )}
                    onSubmit={() => form.submit({ preserveScroll: true })}
                    processing={form.processing}
                    submitLabel={t('Save')}
                    submitTest="save-business-settings"
                >
                    <ComboboxField
                        id="timezone"
                        label={t('Time zone')}
                        hint={t(
                            'The business clock: business days, opening hours and reports follow it.',
                        )}
                        value={form.data.timezone}
                        onValueChange={(value) =>
                            form.setData('timezone', value)
                        }
                        options={timeZones}
                        error={form.errors.timezone}
                        searchPlaceholder={t('Search timezones...')}
                    />

                    <TextField
                        form={form}
                        name="locale"
                        label={t('Regional format')}
                        hint={t(
                            'A language and region code, such as en-US or fr-FR: it decides the decimal and thousands separators and where the currency symbol goes.',
                        )}
                        autoComplete="off"
                        placeholder="en-US"
                    />

                    <ComboboxField
                        id="currency"
                        label={t('Currency')}
                        hint={t(
                            'Used for amounts that have no currency of their own.',
                        )}
                        value={form.data.currency}
                        onValueChange={(value) =>
                            form.setData('currency', value)
                        }
                        options={currencies}
                        error={form.errors.currency}
                        searchPlaceholder={t('Search currencies...')}
                    />

                    <SelectField
                        id="distance_unit"
                        label={t('Distance unit')}
                        value={form.data.distance_unit}
                        onValueChange={(value) =>
                            form.setData(
                                'distance_unit',
                                value as App.Enums.DistanceUnit,
                            )
                        }
                        options={[
                            { value: 'km', label: t('Kilometres') },
                            { value: 'mi', label: t('Miles') },
                        ]}
                        error={form.errors.distance_unit}
                        triggerClassName="w-full sm:w-60"
                    />
                </FormCard>

                {preview !== null && (
                    <SectionCard
                        title={t('Preview')}
                        description={t('How figures will read once saved.')}
                    >
                        <dl
                            className="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm"
                            data-test="business-preview"
                        >
                            <dt className="text-muted-foreground">
                                {t('Amount')}
                            </dt>
                            <dd className="tabular-nums">
                                {preview.money(123456)}
                            </dd>
                            <dt className="text-muted-foreground">
                                {t('Percentage')}
                            </dt>
                            <dd className="tabular-nums">
                                {preview.percent(0.125)}
                            </dd>
                            <dt className="text-muted-foreground">
                                {t('Distance')}
                            </dt>
                            <dd className="tabular-nums">
                                {preview.distance(42.195)}
                            </dd>
                        </dl>
                    </SectionCard>
                )}
            </PageStack>
        </>
    );
}
