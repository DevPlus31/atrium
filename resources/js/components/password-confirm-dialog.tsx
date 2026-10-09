import type { FormComponentProps } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useRef } from 'react';
import type { ReactNode } from 'react';
import { FormField } from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

export type PasswordConfirmDialogProps = {
    /** The button that opens the dialog. */
    trigger: ReactNode;
    title: string;
    description: string;
    /** The Wayfinder form to submit with the password (`route.form()`). */
    form: Pick<FormComponentProps, 'action' | 'method'>;
    submitLabel: string;
    /** `data-test` of the submit button. */
    submitTest: string;
    destructive?: boolean;
    /** Id of the password input (unique on the page). */
    passwordId?: string;
};

/**
 * Asks for the account password before a sensitive request (deleting the
 * account, signing other devices out): the password goes with the request,
 * and a wrong one keeps the dialog open with the error and focus on it.
 */
export function PasswordConfirmDialog({
    trigger,
    title,
    description,
    form,
    submitLabel,
    submitTest,
    destructive = false,
    passwordId = 'password',
}: PasswordConfirmDialogProps) {
    const { t } = useLaravelReactI18n();
    const passwordInput = useRef<HTMLInputElement>(null);

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onError={() => passwordInput.current?.focus()}
                    resetOnSuccess
                    className="space-y-6"
                >
                    {({ resetAndClearErrors, processing, errors }) => (
                        <>
                            <FormField
                                id={passwordId}
                                label={t('Password')}
                                error={errors.password}
                                hideLabel
                            >
                                <PasswordInput
                                    id={passwordId}
                                    name="password"
                                    ref={passwordInput}
                                    placeholder={t('Password')}
                                    autoComplete="current-password"
                                />
                            </FormField>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button
                                        variant="secondary"
                                        onClick={() => resetAndClearErrors()}
                                    >
                                        {t('Cancel')}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant={
                                        destructive ? 'destructive' : 'default'
                                    }
                                    disabled={processing}
                                    data-test={submitTest}
                                >
                                    {submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
