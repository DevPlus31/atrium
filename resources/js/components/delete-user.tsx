import { useLaravelReactI18n } from 'laravel-react-i18n';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import { PasswordConfirmDialog } from '@/components/password-confirm-dialog';
import { SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';

export default function DeleteUser() {
    const { t } = useLaravelReactI18n();

    return (
        <SettingsSection
            title={t('Delete account')}
            description={t('Delete your account and all of its resources')}
        >
            <div className="space-y-4 rounded-lg border border-destructive/20 bg-destructive/10 p-4">
                <div className="relative space-y-0.5 text-destructive">
                    <p className="font-medium">{t('Warning')}</p>
                    <p className="text-sm">
                        {t(
                            'Please proceed with caution, this cannot be undone.',
                        )}
                    </p>
                </div>

                <PasswordConfirmDialog
                    trigger={
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            {t('Delete account')}
                        </Button>
                    }
                    title={t('Are you sure you want to delete your account?')}
                    description={t(
                        'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
                    )}
                    form={AccountController.destroy.form()}
                    submitLabel={t('Delete account')}
                    submitTest="confirm-delete-user-button"
                    destructive
                />
            </div>
        </SettingsSection>
    );
}
