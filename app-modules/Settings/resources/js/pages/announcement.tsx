import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { fromZonedInput, toZonedInput } from '@/lib/format';
import { destroy, edit, update } from '@/routes/admin/settings/announcement';
import type { BreadcrumbItem } from '@/types';

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

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Announcement'), href: edit() },
    ];
    useBreadcrumbs(breadcrumbs);

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

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={t('Announcement')} />
            <Card className="max-w-2xl">
                <CardHeader>
                    <div className="flex items-center gap-2">
                        <CardTitle>{t('Announcement')}</CardTitle>
                        {settings.is_active && (
                            <Badge variant="secondary">{t('Showing')}</Badge>
                        )}
                    </div>
                    <CardDescription>
                        {t(
                            'A notice at the top of every page, sign-in pages included, until it ends or you remove it. Each person can dismiss it.',
                        )}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="message">{t('Message')}</Label>
                            <Textarea
                                id="message"
                                rows={3}
                                maxLength={maxLength}
                                value={form.data.message}
                                onChange={(event) =>
                                    form.setData('message', event.target.value)
                                }
                                placeholder={t(
                                    'e.g. Planned maintenance on Sunday from 2:00 to 4:00.',
                                )}
                            />
                            <p className="text-xs text-muted-foreground">
                                {form.data.message.length} / {maxLength}
                            </p>
                            <InputError message={form.errors.message} />
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="level">{t('Level')}</Label>
                                <Select
                                    value={form.data.level}
                                    onValueChange={(value) =>
                                        form.setData('level', value as Level)
                                    }
                                >
                                    <SelectTrigger id="level">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(levels).map(
                                            ([value, label]) => (
                                                <SelectItem
                                                    key={value}
                                                    value={value}
                                                >
                                                    {t(label)}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.level} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="ends_at">
                                    {t('Ends (optional)')}
                                </Label>
                                <Input
                                    id="ends_at"
                                    type="datetime-local"
                                    value={form.data.ends_at}
                                    onChange={(event) =>
                                        form.setData(
                                            'ends_at',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.ends_at} />
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                disabled={form.processing}
                                data-test="publish-announcement"
                            >
                                {form.processing && <Spinner />}
                                {t('Publish')}
                            </Button>
                            {settings.message !== null && (
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
                            )}
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
