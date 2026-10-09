import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { FormCard } from '@/components/form-card';
import { SelectField } from '@/components/select-field';
import { TextField } from '@/components/text-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { fromZonedInput, toZonedInput } from '@/lib/format';
import { destroy, edit, update } from '@/routes/admin/settings/announcement';

type AnnouncementProps = {
    // Not "announcement": that name is the shared banner prop.
    settings: Modules.Settings.Data.AnnouncementSettingsData;
    maxLength: number;
};

type Level = App.Enums.AnnouncementLevel;

const levels: Record<Level, string> = {
    info: 'Information',
    warning: 'Warning',
};

export default function Announcement({
    settings,
    maxLength,
}: AnnouncementProps) {
    const { t } = useLaravelReactI18n();
    // The user's chosen time zone; none means the browser's own.
    const timeZone = usePage().props.timezone ?? undefined;

    useBreadcrumbs({ title: t('Announcement'), href: edit() });

    const form = useForm(update(), {
        message: settings.message ?? '',
        level: settings.level,
        ends_at: toZonedInput(settings.ends_at, timeZone),
    });

    // The input holds wall-clock time in the user's zone; the server
    // stores an instant.
    form.transform((data) => ({
        ...data,
        ends_at: data.ends_at ? fromZonedInput(data.ends_at, timeZone) : null,
    }));

    return (
        <>
            <Head title={t('Announcement')} />
            <FormCard
                title={t('Announcement')}
                badge={
                    settings.is_active && (
                        <Badge variant="secondary">{t('Showing')}</Badge>
                    )
                }
                description={t(
                    'A notice at the top of every page, sign-in pages included, until it ends or you remove it. Each person can dismiss it.',
                )}
                onSubmit={() => form.submit({ preserveScroll: true })}
                processing={form.processing}
                submitLabel={t('Publish')}
                submitTest="publish-announcement"
                actions={
                    settings.message !== null && (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() =>
                                router.delete(destroy.url(), {
                                    preserveScroll: true,
                                    onSuccess: () =>
                                        form.setData({
                                            message: '',
                                            level: 'info',
                                            ends_at: '',
                                        }),
                                })
                            }
                        >
                            {t('Remove')}
                        </Button>
                    )
                }
            >
                <TextField
                    form={form}
                    name="message"
                    rows={3}
                    maxLength={maxLength}
                    validateOnBlur={false}
                    label={t('Message')}
                    hint={`${form.data.message.length} / ${maxLength}`}
                    placeholder={t(
                        'e.g. Planned maintenance on Sunday from 2:00 to 4:00.',
                    )}
                />

                <div className="grid gap-2 sm:grid-cols-2">
                    <SelectField
                        id="level"
                        label={t('Level')}
                        error={form.errors.level}
                        value={form.data.level}
                        onValueChange={(value) =>
                            form.setData('level', value as Level)
                        }
                        options={Object.entries(levels).map(
                            ([value, label]) => ({ value, label: t(label) }),
                        )}
                    />

                    <TextField
                        form={form}
                        name="ends_at"
                        type="datetime-local"
                        validateOnBlur={false}
                        label={t('Ends (optional)')}
                    />
                </div>
            </FormCard>
        </>
    );
}
