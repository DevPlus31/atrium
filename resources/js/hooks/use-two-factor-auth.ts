import { useHttp } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { qrCode, recoveryCodes, secretKey } from '@/routes/two-factor';
import type { TwoFactorSecretKey, TwoFactorSetupData } from '@/types';

export type UseTwoFactorAuthReturn = {
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    recoveryCodesList: string[];
    hasSetupData: boolean;
    errors: string[];
    clearErrors: () => void;
    clearSetupData: () => void;
    fetchQrCode: () => Promise<void>;
    fetchSetupKey: () => Promise<void>;
    fetchSetupData: () => Promise<void>;
    fetchRecoveryCodes: () => Promise<void>;
};

export const OTP_MAX_LENGTH = 6;

type NoData = Record<string, never>;

/**
 * Fortify's two-factor endpoints answer plain JSON (no page visit), fetched
 * with Inertia's useHttp: it sends the session's headers and drops a response
 * that arrives after the component unmounted.
 */
export const useTwoFactorAuth = (): UseTwoFactorAuthReturn => {
    const { t } = useLaravelReactI18n();
    const qrCodeHttp = useHttp<NoData, TwoFactorSetupData>({});
    const secretKeyHttp = useHttp<NoData, TwoFactorSecretKey>({});
    const recoveryCodesHttp = useHttp<NoData, string[]>({});
    const [qrCodeSvg, setQrCodeSvg] = useState<string | null>(null);
    const [manualSetupKey, setManualSetupKey] = useState<string | null>(null);
    const [recoveryCodesList, setRecoveryCodesList] = useState<string[]>([]);
    const [errors, setErrors] = useState<string[]>([]);

    const hasSetupData = qrCodeSvg !== null && manualSetupKey !== null;

    const addError = (message: string): void =>
        setErrors((previous) => [...previous, message]);

    const fetchQrCode = async (): Promise<void> => {
        try {
            const { svg } = await qrCodeHttp.get(qrCode.url());
            setQrCodeSvg(svg);
        } catch {
            addError(t('Failed to fetch QR code'));
            setQrCodeSvg(null);
        }
    };

    const fetchSetupKey = async (): Promise<void> => {
        try {
            const { secretKey: key } = await secretKeyHttp.get(secretKey.url());
            setManualSetupKey(key);
        } catch {
            addError(t('Failed to fetch a setup key'));
            setManualSetupKey(null);
        }
    };

    const clearErrors = (): void => {
        setErrors([]);
    };

    const clearSetupData = (): void => {
        setManualSetupKey(null);
        setQrCodeSvg(null);
        clearErrors();
    };

    const fetchRecoveryCodes = async (): Promise<void> => {
        try {
            clearErrors();
            setRecoveryCodesList(
                await recoveryCodesHttp.get(recoveryCodes.url()),
            );
        } catch {
            addError(t('Failed to fetch recovery codes'));
            setRecoveryCodesList([]);
        }
    };

    const fetchSetupData = async (): Promise<void> => {
        clearErrors();
        await Promise.all([fetchQrCode(), fetchSetupKey()]);
    };

    return {
        qrCodeSvg,
        manualSetupKey,
        recoveryCodesList,
        hasSetupData,
        errors,
        clearErrors,
        clearSetupData,
        fetchQrCode,
        fetchSetupKey,
        fetchSetupData,
        fetchRecoveryCodes,
    };
};
