import { Head, router } from '@inertiajs/react';
import { usePasskeyRegister } from '@laravel/passkeys/react';
import { KeyRound, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { destroy } from '@/routes/passkey';
import { show } from '@/routes/passkeys';
import type { BreadcrumbItem } from '@/types';

type PasskeyItem = {
    id: number;
    name: string;
    authenticator: string | null;
    last_used_at: string | null;
    created_at: string | null;
};

type Props = {
    canManagePasskeys?: boolean;
    passkeys?: PasskeyItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Passkeys',
        href: show(),
    },
];

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function Passkeys({
    canManagePasskeys = false,
    passkeys = [],
}: Props) {
    const [name, setName] = useState<string>('');
    const [pendingDelete, setPendingDelete] = useState<PasskeyItem | null>(
        null,
    );
    const [deleting, setDeleting] = useState<boolean>(false);

    const { register, isLoading, error, isSupported } = usePasskeyRegister({
        onSuccess: () => {
            setName('');
            router.reload({ only: ['passkeys'] });
        },
    });

    const confirmDelete = () => {
        if (pendingDelete === null) {
            return;
        }

        router.delete(destroy.url(pendingDelete.id), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Passkeys" />
            <SettingsLayout>
                {canManagePasskeys && (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Passkeys"
                            description="Sign in securely with your device's screen lock or a hardware key"
                        />

                        <div className="flex flex-col items-start justify-start space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Passkeys replace your password and one-time
                                codes during login. Your device verifies your
                                identity with a fingerprint, face, or PIN and
                                never shares that data with us.
                            </p>

                            <form
                                className="flex w-full flex-col gap-2"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    void register(name);
                                }}
                            >
                                <Label htmlFor="passkey-name">
                                    Passkey name
                                </Label>
                                <div className="flex w-full items-center gap-2">
                                    <Input
                                        id="passkey-name"
                                        value={name}
                                        onChange={(event) =>
                                            setName(event.target.value)
                                        }
                                        placeholder="e.g. Work laptop"
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
                                        {isLoading ? <Spinner /> : <KeyRound />}
                                        Add passkey
                                    </Button>
                                </div>
                                {!isSupported && (
                                    <p className="text-sm text-muted-foreground">
                                        This browser does not support passkeys.
                                    </p>
                                )}
                                <InputError message={error ?? undefined} />
                            </form>
                        </div>

                        {passkeys.length > 0 && (
                            <ul className="divide-y rounded-lg border">
                                {passkeys.map((passkey) => (
                                    <li
                                        key={passkey.id}
                                        className="flex items-center gap-4 p-4"
                                    >
                                        <KeyRound className="size-4 shrink-0 text-muted-foreground" />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {passkey.name}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {passkey.authenticator !== null
                                                    ? `${passkey.authenticator} · `
                                                    : ''}
                                                {passkey.last_used_at !== null
                                                    ? `Last used ${formatDate(passkey.last_used_at)}`
                                                    : passkey.created_at !==
                                                        null
                                                      ? `Added ${formatDate(passkey.created_at)}`
                                                      : 'Never used'}
                                            </p>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="ms-auto text-destructive hover:text-destructive"
                                            aria-label={`Delete passkey ${passkey.name}`}
                                            onClick={() =>
                                                setPendingDelete(passkey)
                                            }
                                        >
                                            <Trash2 />
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <ConfirmDialog
                            open={pendingDelete !== null}
                            onOpenChange={(open) => {
                                if (!open && !deleting) {
                                    setPendingDelete(null);
                                }
                            }}
                            title="Delete passkey"
                            description={`This will permanently delete ${pendingDelete?.name ?? 'this passkey'} and it can no longer be used to sign in.`}
                            confirmLabel="Delete"
                            processing={deleting}
                            onConfirm={confirmDelete}
                        />
                    </div>
                )}
            </SettingsLayout>
        </AppLayout>
    );
}
