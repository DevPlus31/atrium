import { useForm, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { KeyRound } from 'lucide-react';
import { CheckboxGroupField, CheckboxList } from '@/components/checkbox-group';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { CopyField } from '@/components/copy-field';
import { EmptyState } from '@/components/empty-state';
import { ItemList, ItemRow } from '@/components/item-list';
import { SelectField } from '@/components/select-field';
import { SettingsSection } from '@/components/settings-section';
import { TextField } from '@/components/text-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import { SettingsPage } from '@/layouts/settings/page';
import { handleSubmit } from '@/lib/utils';
import { destroy, index, store } from '@/routes/api-tokens';

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
    const revokeDialog = useDeleteDialog<ApiToken>((token) =>
        destroy.url(token.id),
    );

    const form = useForm(store(), {
        name: '',
        expires_in_days: lifetimes.at(1) ?? lifetimes[0],
        abilities: [] as string[],
    });

    const allAbilities = form.data.abilities.length === abilities.length;

    return (
        <>
            <SettingsPage title={t('API tokens')} href={index()}>
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
                        <CopyField
                            value={newToken}
                            label={t('New API token')}
                            copyLabel={t('Copy token')}
                            inputClassName="text-xs"
                        />
                    </div>
                )}

                <SettingsSection
                    title={t('Create an API token')}
                    description={t(
                        'Send it as a Bearer token to /api/v1. It can only do what the permissions you tick allow, and never more than you can.',
                    )}
                >
                    <form
                        onSubmit={handleSubmit(() =>
                            form.submit({
                                preserveScroll: true,
                                onSuccess: () => form.reset(),
                            }),
                        )}
                        className="space-y-6"
                    >
                        <TextField
                            form={form}
                            name="name"
                            id="token_name"
                            validateOnBlur={false}
                            label={t('Name')}
                            placeholder={t('e.g. Reporting script')}
                            autoComplete="off"
                        />

                        <SelectField
                            id="token_expiry"
                            label={t('Expires')}
                            error={form.errors.expires_in_days}
                            value={String(form.data.expires_in_days)}
                            onValueChange={(value) =>
                                form.setData('expires_in_days', Number(value))
                            }
                            options={lifetimes.map((days) => ({
                                value: String(days),
                                label: t('In :days days', {
                                    days: String(days),
                                }),
                            }))}
                            triggerClassName="w-full sm:w-60"
                        />

                        {abilities.length > 0 && (
                            <CheckboxGroupField
                                legend={t('Permissions')}
                                legendAction={
                                    <Button
                                        type="button"
                                        variant="link"
                                        size="sm"
                                        className="h-auto p-0 text-xs"
                                        onClick={() =>
                                            form.setData(
                                                'abilities',
                                                allAbilities ? [] : abilities,
                                            )
                                        }
                                    >
                                        {allAbilities
                                            ? t('Clear all')
                                            : t('Select all')}
                                    </Button>
                                }
                                error={form.errors.abilities}
                            >
                                <CheckboxList
                                    className="sm:grid-cols-2"
                                    optionClassName="font-mono text-xs"
                                    options={abilities}
                                    selected={form.data.abilities}
                                    onChange={(next) =>
                                        form.setData('abilities', next)
                                    }
                                />
                            </CheckboxGroupField>
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
                </SettingsSection>

                <SettingsSection
                    title={t('Your tokens')}
                    description={t(
                        'Revoke a token you no longer use, or one that may have leaked.',
                    )}
                >
                    {tokens.length === 0 ? (
                        <EmptyState>{t('You have no API tokens.')}</EmptyState>
                    ) : (
                        <ItemList>
                            {tokens.map((token) => (
                                <ItemRow
                                    key={token.id}
                                    test="api-token"
                                    title={token.name}
                                    details={[
                                        token.last_used_at
                                            ? t('Last used :date', {
                                                  date: date(
                                                      token.last_used_at,
                                                  ),
                                              })
                                            : t('Never used'),
                                        token.expires_at
                                            ? t('expires :date', {
                                                  date: date(token.expires_at),
                                              })
                                            : t('never expires'),
                                    ]}
                                    extra={
                                        <span>
                                            <Badge variant="secondary">
                                                {tChoice(
                                                    ':count permission|:count permissions',
                                                    token.abilities.length,
                                                )}
                                            </Badge>
                                        </span>
                                    }
                                    actions={
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            aria-label={t(
                                                'Revoke the token :name',
                                                { name: token.name },
                                            )}
                                            onClick={() =>
                                                revokeDialog.request(token)
                                            }
                                        >
                                            {t('Revoke')}
                                        </Button>
                                    }
                                />
                            ))}
                        </ItemList>
                    )}
                </SettingsSection>
            </SettingsPage>

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
