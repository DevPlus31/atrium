import { UserAvatar } from '@/components/user-avatar';

export type UserInfoUser = {
    name: string;
    email: string;
    avatar: string | null;
};

/** A user's avatar beside their name (and email), for menus, rows and cards. */
export function UserInfo({
    user,
    showEmail = false,
}: {
    user: UserInfoUser;
    showEmail?: boolean;
}) {
    return (
        <>
            <UserAvatar name={user.name} avatar={user.avatar} />
            <div className="grid flex-1 text-start text-sm leading-tight">
                <span className="truncate font-medium">{user.name}</span>
                {showEmail && (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}
