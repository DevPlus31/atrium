import { router } from '@inertiajs/react';
import { usePasskeyRegister } from '@laravel/passkeys/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { KeyRound, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormField } from '@/components/form-field';
import { ItemList, ItemRow } from '@/components/item-list';
import { SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import { SettingsPage } from '@/layouts/settings/page';
import { handleSubmit } from '@/lib/utils';
import { destroy } from '@/routes/passkey';
import { show } from '@/routes/passkeys';

type PasskeyItem = App.Modules.Data.PasskeyData;

type Props = {
    canManagePasskeys?: boolean;
    passkeys?: PasskeyItem[];
};

export default function Passkeys({
    canManagePasskeys = false,
    passkeys = [],
}: Props) {
    const { t } = useLaravelReactI18n();
    const format = useFormatters();
    const [name, setName] = useState<string>('');
    const deleteDialog = useDeleteDialog<PasskeyItem>((row) =>
        destroy.url(row.id),
    );

    const { register, isLoading, error, isSupported } = usePasskeyRegister({
        onSuccess: () => {
            setName('');
            router.reload({ only: ['passkeys'] });
        },
    });

    return (
        <>
            <SettingsPage title={t('Passkeys')} href={show()}>
                {canManagePasskeys && (
                    <SettingsSection
                        title={t('Passkeys')}
                        description={t(
                            "Sign in securely with your device's screen lock or a hardware key",
                        )}
                    >
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <p className="text-sm text-muted-foreground">
                                {t(
                                    'Passkeys replace your password and one-time codes during login. Your device verifies your identity with a fingerprint, face, or PIN and never shares that data with us.',
                                )}
                            </p>

                            <form
                                className="w-full"
                                onSubmit={handleSubmit(
                                    () => void register(name),
                                )}
                            >
                                <FormField
                                    id="passkey-name"
                                    label={t('Passkey name')}
                                    error={error ?? undefined}
                                    hint={
                                        isSupported
                                            ? undefined
                                            : t(
                                                  'This browser does not support passkeys.',
                                              )
                                    }
                                >
                                    <div className="flex w-full items-center gap-2">
                                        <Input
                                            id="passkey-name"
                                            value={name}
                                            onChange={(event) =>
                                                setName(event.target.value)
                                            }
                                            placeholder={t('e.g. Work laptop')}
                                            maxLength={255}
                                        />
                                        <Button
                                            type="submit"
                                            disabled={
                                                !isSupported ||
                                                isLoading ||
                                                name.trim() === ''
                                            }
                                        >
                                            {isLoading ? (
                                                <Spinner />
                                            ) : (
                                                <KeyRound />
                                            )}
                                            {t('Add passkey')}
                                        </Button>
                                    </div>
                                </FormField>
                            </form>
                        </div>

                        {passkeys.length > 0 && (
                            <ItemList>
                                {passkeys.map((passkey) => (
                                    <ItemRow
                                        key={passkey.id}
                                        icon={
                                            <KeyRound className="size-4 shrink-0 text-muted-foreground" />
                                        }
                                        title={passkey.name}
                                        details={[
                                            passkey.authenticator,
                                            passkey.last_used_at !== null
                                                ? t('Last used :date', {
                                                      date: format.date(
                                                          passkey.last_used_at,
                                                      ),
                                                  })
                                                : passkey.created_at !== null
                                                  ? t('Added :date', {
                                                        date: format.date(
                                                            passkey.created_at,
                                                        ),
                                                    })
                                                  : t('Never used'),
                                        ]}
                                        actions={
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive hover:text-destructive"
                                                aria-label={t(
                                                    'Delete passkey :name',
                                                    { name: passkey.name },
                                                )}
                                                onClick={() =>
                                                    deleteDialog.request(
                                                        passkey,
                                                    )
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        }
                                    />
                                ))}
                            </ItemList>
                        )}

                        <ConfirmDialog
                            {...deleteDialog.dialogProps}
                            title={t('Delete passkey')}
                            description={t(
                                'This will permanently delete :name and it can no longer be used to sign in.',
                                {
                                    name:
                                        deleteDialog.pending?.name ??
                                        t('this passkey'),
                                },
                            )}
                            confirmLabel={t('Delete')}
                        />
                    </SettingsSection>
                )}
            </SettingsPage>
        </>
    );
}
