import { Head, useForm, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Check, Copy, KeyRound } from 'lucide-react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useClipboard } from '@/hooks/use-clipboard';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import SettingsLayout from '@/layouts/settings/layout';
import { destroy, index, store } from '@/routes/api-tokens';
import type { BreadcrumbItem } from '@/types';

type ApiToken = Modules.Api.Data.ApiTokenData;

type ApiTokensProps = {
    tokens: ApiToken[];
    /** The permissions the user holds, which a token may carry. */
    abilities: string[];
    /** Lifetimes offered, in days; every token expires. */
    lifetimes: number[];
};

export default function ApiTokens({
    tokens,
    abilities,
    lifetimes,
}: ApiTokensProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const { date } = useFormatters();
    const flashed = usePage().flash.apiToken;
    const newToken = typeof flashed === 'string' ? flashed : null;
    const [copied, copy] = useClipboard();
    const revokeDialog = useDeleteDialog<ApiToken>((token) =>
        destroy.url(token.id),
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('API tokens'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    const form = useForm(store(), {
        name: '',
        expires_in_days: lifetimes.at(1) ?? lifetimes[0],
        abilities: [] as string[],
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.submit({ preserveScroll: true, onSuccess: () => form.reset() });
    };

    const toggleAbility = (ability: string, checked: boolean) => {
        form.setData(
            'abilities',
            checked
                ? [...form.data.abilities, ability]
                : form.data.abilities.filter((value) => value !== ability),
        );
    };

    return (
        <>
            <Head title={t('API tokens')} />

            <h1 className="sr-only">{t('API tokens')}</h1>

            <SettingsLayout>
                {newToken && (
                    <div
                        className="space-y-3 rounded-lg border border-primary/40 bg-primary/5 p-4"
                        data-test="new-api-token"
                    >
                        <p className="text-sm font-medium">
                            {t(
                                "Copy your new token now. You won't be able to see it again.",
                            )}
                        </p>
                        <div className="flex gap-2">
                            <Input
                                value={newToken}
                                readOnly
                                aria-label={t('New API token')}
                                className="font-mono text-xs"
                                onFocus={(event) => event.target.select()}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                aria-label={t('Copy token')}
                                onClick={() => void copy(newToken)}
                            >
                                {copied === newToken ? <Check /> : <Copy />}
                            </Button>
                        </div>
                    </div>
                )}

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('Create an API token')}
                        description={t(
                            'Send it as a Bearer token to /api/v1. It can only do what the permissions you tick allow, and never more than you can.',
                        )}
                    />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="token_name">{t('Name')}</Label>
                            <Input
                                id="token_name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                placeholder={t('e.g. Reporting script')}
                                autoComplete="off"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="token_expiry">{t('Expires')}</Label>
                            <Select
                                value={String(form.data.expires_in_days)}
                                onValueChange={(value) =>
                                    form.setData(
                                        'expires_in_days',
                                        Number(value),
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="token_expiry"
                                    className="w-full sm:w-60"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {lifetimes.map((days) => (
                                        <SelectItem
                                            key={days}
                                            value={String(days)}
                                        >
                                            {t('In :days days', {
                                                days: String(days),
                                            })}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.expires_in_days} />
                        </div>

                        {abilities.length > 0 && (
                            <fieldset className="grid gap-2">
                                <div className="flex items-center justify-between">
                                    <legend className="text-sm font-medium">
                                        {t('Permissions')}
                                    </legend>
                                    <Button
                                        type="button"
                                        variant="link"
                                        size="sm"
                                        className="h-auto p-0 text-xs"
                                        onClick={() =>
                                            form.setData(
                                                'abilities',
                                                form.data.abilities.length ===
                                                    abilities.length
                                                    ? []
                                                    : abilities,
                                            )
                                        }
                                    >
                                        {form.data.abilities.length ===
                                        abilities.length
                                            ? t('Clear all')
                                            : t('Select all')}
                                    </Button>
                                </div>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {abilities.map((ability) => (
                                        <label
                                            key={ability}
                                            className="flex items-center gap-2 font-mono text-xs"
                                        >
                                            <Checkbox
                                                checked={form.data.abilities.includes(
                                                    ability,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggleAbility(
                                                        ability,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            {ability}
                                        </label>
                                    ))}
                                </div>
                                <InputError message={form.errors.abilities} />
                            </fieldset>
                        )}

                        <Button
                            type="submit"
                            disabled={form.processing}
                            data-test="create-api-token"
                        >
                            {form.processing ? <Spinner /> : <KeyRound />}
                            {t('Create token')}
                        </Button>
                    </form>
                </div>

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('Your tokens')}
                        description={t(
                            'Revoke a token you no longer use, or one that may have leaked.',
                        )}
                    />

                    {tokens.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('You have no API tokens.')}
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border">
                            {tokens.map((token) => (
                                <li
                                    key={token.id}
                                    className="flex flex-wrap items-center gap-3 p-3"
                                    data-test="api-token"
                                >
                                    <div className="grid min-w-0 flex-1 gap-1">
                                        <span className="truncate text-sm font-medium">
                                            {token.name}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {[
                                                token.last_used_at
                                                    ? t('Last used :date', {
                                                          date: date(
                                                              token.last_used_at,
                                                          ),
                                                      })
                                                    : t('Never used'),
                                                token.expires_at
                                                    ? t('expires :date', {
                                                          date: date(
                                                              token.expires_at,
                                                          ),
                                                      })
                                                    : t('never expires'),
                                            ].join(' · ')}
                                        </span>
                                        <span>
                                            <Badge variant="secondary">
                                                {tChoice(
                                                    ':count permission|:count permissions',
                                                    token.abilities.length,
                                                )}
                                            </Badge>
                                        </span>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="text-destructive"
                                        aria-label={t(
                                            'Revoke the token :name',
                                            {
                                                name: token.name,
                                            },
                                        )}
                                        onClick={() =>
                                            revokeDialog.request(token)
                                        }
                                    >
                                        {t('Revoke')}
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </SettingsLayout>

            <ConfirmDialog
                {...revokeDialog.dialogProps}
                title={t('Revoke token')}
                description={t('Anything using :name will stop working.', {
                    name: revokeDialog.pending?.name ?? '',
                })}
                confirmLabel={t('Revoke')}
            />
        </>
    );
}
