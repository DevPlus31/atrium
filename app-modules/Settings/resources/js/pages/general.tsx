import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ImageIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ImageInput } from '@/components/image-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, update } from '@/routes/admin/settings/general';
import {
    destroy as removeLogo,
    update as uploadLogo,
} from '@/routes/admin/settings/logo';
import type { BreadcrumbItem } from '@/types';

type GeneralSettingsProps = {
    settings: Modules.Settings.Data.GeneralSettingsData;
};

export default function GeneralSettings({ settings }: GeneralSettingsProps) {
    const { t } = useLaravelReactI18n();
    const { errors } = usePage().props;
    const [uploading, setUploading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('General settings'), href: edit() },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm(update(), {
        support_email: settings.support_email ?? '',
        registration_open: settings.registration_open,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={t('General settings')} />
            <div className="grid max-w-2xl gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Logo')}</CardTitle>
                        <CardDescription>
                            {t(
                                'Shown in the menu, on the sign-in pages and on error pages instead of the built-in mark.',
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ImageInput
                            label={t('Logo')}
                            shape="square"
                            imageUrl={settings.logo}
                            fallback={
                                <ImageIcon
                                    className="size-6"
                                    aria-hidden="true"
                                />
                            }
                            error={errors.logo}
                            processing={uploading}
                            onSelect={(file) =>
                                router.post(
                                    uploadLogo.url(),
                                    { logo: file },
                                    {
                                        forceFormData: true,
                                        preserveScroll: true,
                                        onStart: () => setUploading(true),
                                        onFinish: () => setUploading(false),
                                    },
                                )
                            }
                            onRemove={() =>
                                router.delete(removeLogo.url(), {
                                    preserveScroll: true,
                                })
                            }
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('General settings')}</CardTitle>
                        <CardDescription>
                            {t('Settings that apply to the whole app.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="support_email">
                                    {t('Support email')}
                                </Label>
                                <Input
                                    id="support_email"
                                    type="email"
                                    autoComplete="off"
                                    value={form.data.support_email}
                                    onChange={(event) =>
                                        form.setData(
                                            'support_email',
                                            event.target.value,
                                        )
                                    }
                                    onBlur={() =>
                                        form.validate('support_email')
                                    }
                                    placeholder="support@example.com"
                                />
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'Shown on error pages so people know who to ask. Leave empty to hide it.',
                                    )}
                                </p>
                                <InputError
                                    message={form.errors.support_email}
                                />
                            </div>

                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="registration_open"
                                    checked={form.data.registration_open}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'registration_open',
                                            checked === true,
                                        )
                                    }
                                />
                                <div className="grid gap-1">
                                    <Label htmlFor="registration_open">
                                        {t('Allow sign-ups')}
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        {t(
                                            'When off, only administrators can create accounts.',
                                        )}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    {t('Save')}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
