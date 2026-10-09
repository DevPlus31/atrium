import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

/** A user's profile photo, or their initials when they have none. */
export function UserAvatar({
    name,
    avatar,
    className,
}: {
    name: string;
    avatar: string | null;
    className?: string;
}) {
    const getInitials = useInitials();

    return (
        <Avatar
            className={cn('size-8 overflow-hidden rounded-full', className)}
        >
            {avatar && <AvatarImage src={avatar} alt="" />}
            <AvatarFallback className="bg-muted text-xs font-medium text-muted-foreground">
                {getInitials(name)}
            </AvatarFallback>
        </Avatar>
    );
}
