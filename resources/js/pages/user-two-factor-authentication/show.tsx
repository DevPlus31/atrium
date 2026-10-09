import { Form } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { SettingsSection } from '@/components/settings-section';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import { SettingsPage } from '@/layouts/settings/page';
import { disable, enable, show } from '@/routes/two-factor';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

export default function TwoFactor({
    canManageTwoFactor = false,
    requiresConfirmation = false,
    twoFactorEnabled = false,
}: Props) {
    const { t } = useLaravelReactI18n();
    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        clearErrors,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);

    return (
        <>
            <SettingsPage title={t('Two-factor authentication')} href={show()}>
                {canManageTwoFactor && (
                    <SettingsSection
                        title={t('Two-factor authentication')}
                        description={t(
                            'Manage your two-factor authentication settings',
                        )}
                    >
                        {twoFactorEnabled ? (
                            <div className="flex flex-col items-start justify-start space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
                                    )}
                                </p>

                                <div className="relative inline">
                                    <Form {...disable.form()}>
                                        {({ processing }) => (
                                            <Button
                                                variant="destructive"
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {t('Disable 2FA')}
                                            </Button>
                                        )}
                                    </Form>
                                </div>

                                <TwoFactorRecoveryCodes
                                    recoveryCodesList={recoveryCodesList}
                                    fetchRecoveryCodes={fetchRecoveryCodes}
                                    errors={errors}
                                />
                            </div>
                        ) : (
                            <div className="flex flex-col items-start justify-start space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
                                    )}
                                </p>

                                <div>
                                    {hasSetupData ? (
                                        <Button
                                            onClick={() =>
                                                setShowSetupModal(true)
                                            }
                                        >
                                            <ShieldCheck />
                                            {t('Continue setup')}
                                        </Button>
                                    ) : (
                                        <Form
                                            {...enable.form()}
                                            onSuccess={() =>
                                                setShowSetupModal(true)
                                            }
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    {t('Enable 2FA')}
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </div>
                            </div>
                        )}

                        <TwoFactorSetupModal
                            isOpen={showSetupModal}
                            onClose={() => setShowSetupModal(false)}
                            requiresConfirmation={requiresConfirmation}
                            twoFactorEnabled={twoFactorEnabled}
                            qrCodeSvg={qrCodeSvg}
                            manualSetupKey={manualSetupKey}
                            clearSetupData={clearSetupData}
                            clearErrors={clearErrors}
                            fetchSetupData={fetchSetupData}
                            errors={errors}
                        />
                    </SettingsSection>
                )}
            </SettingsPage>
        </>
    );
}
