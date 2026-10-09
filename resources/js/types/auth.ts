export type User = App.Modules.Data.AuthUserData;

export type Auth = {
    user: User;
};

// Fortify's own JSON responses (not our DTOs), so they cannot be generated.
export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
