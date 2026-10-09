import { useHttp } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';
import { qrCode, recoveryCodes, secretKey } from '@/routes/two-factor';
import type { TwoFactorSecretKey, TwoFactorSetupData } from '@/types';

export type UseTwoFactorAuthReturn = {
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    recoveryCodesList: string[];
    hasSetupData: boolean;
    /** Translation keys of what failed; AlertError translates them. */
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
 * that arrives after the component unmounted. The fetchers are stable and
 * never overlap, so a component may call them from an effect; after a
 * failure they are not retried until the errors are cleared.
 */
export const useTwoFactorAuth = (): UseTwoFactorAuthReturn => {
    const { get: getQrCode } = useHttp<NoData, TwoFactorSetupData>({});
    const { get: getSecretKey } = useHttp<NoData, TwoFactorSecretKey>({});
    const { get: getRecoveryCodes } = useHttp<NoData, string[]>({});
    const [qrCodeSvg, setQrCodeSvg] = useState<string | null>(null);
    const [manualSetupKey, setManualSetupKey] = useState<string | null>(null);
    const [recoveryCodesList, setRecoveryCodesList] = useState<string[]>([]);
    const [errors, setErrors] = useState<string[]>([]);
    const inFlight = useRef({ setup: false, recoveryCodes: false });

    const hasSetupData = qrCodeSvg !== null && manualSetupKey !== null;

    const addError = useCallback(
        (key: string): void =>
            setErrors((previous) =>
                previous.includes(key) ? previous : [...previous, key],
            ),
        [],
    );

    const fetchQrCode = useCallback(async (): Promise<void> => {
        try {
            const { svg } = await getQrCode(qrCode.url());
            setQrCodeSvg(svg);
        } catch {
            addError('Failed to fetch QR code');
            setQrCodeSvg(null);
        }
    }, [getQrCode, addError]);

    const fetchSetupKey = useCallback(async (): Promise<void> => {
        try {
            const { secretKey: key } = await getSecretKey(secretKey.url());
            setManualSetupKey(key);
        } catch {
            addError('Failed to fetch a setup key');
            setManualSetupKey(null);
        }
    }, [getSecretKey, addError]);

    const clearErrors = useCallback((): void => setErrors([]), []);

    const clearSetupData = useCallback((): void => {
        setManualSetupKey(null);
        setQrCodeSvg(null);
        setErrors([]);
    }, []);

    const fetchRecoveryCodes = useCallback(async (): Promise<void> => {
        if (inFlight.current.recoveryCodes) {
            return;
        }

        inFlight.current.recoveryCodes = true;

        try {
            setErrors([]);
            setRecoveryCodesList(await getRecoveryCodes(recoveryCodes.url()));
        } catch {
            addError('Failed to fetch recovery codes');
        } finally {
            inFlight.current.recoveryCodes = false;
        }
    }, [getRecoveryCodes, addError]);

    const fetchSetupData = useCallback(async (): Promise<void> => {
        if (inFlight.current.setup) {
            return;
        }

        inFlight.current.setup = true;

        try {
            setErrors([]);
            await Promise.all([fetchQrCode(), fetchSetupKey()]);
        } finally {
            inFlight.current.setup = false;
        }
    }, [fetchQrCode, fetchSetupKey]);

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
