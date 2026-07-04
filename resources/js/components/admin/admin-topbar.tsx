import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, Search } from 'lucide-react';
import { groupNavItems, resolveNavIcon } from '@/components/admin/nav';
import { ThemeSettingsMenu } from '@/components/admin/theme-settings-menu';
import AppLogo from '@/components/app-logo';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import type { NavItem } from '@/types/admin';

type AdminTopbarProps = {
    nav: NavItem[];
    breadcrumbs?: BreadcrumbItem[];
    onOpenCommandPalette: () => void;
    className?: string;
};

export function AdminTopbar({
    nav,
    breadcrumbs = [],
    onOpenCommandPalette,
    className,
}: AdminTopbarProps) {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const { isCurrentUrl } = useCurrentUrl();
    const groups = groupNavItems(nav);
    const homeItem = nav.at(0);

    return (
        <header
            className={cn(
                'border-b border-sidebar-border/50 bg-background',
                className,
            )}
        >
            <div className="flex h-16 items-center gap-2 px-6 md:px-4">
                {/* Mobile navigation */}
                <div className="lg:hidden">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="-ms-1"
                            >
                                <Menu />
                                <span className="sr-only">Open navigation</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            className="w-64 overflow-y-auto bg-sidebar"
                        >
                            <SheetTitle className="sr-only">
                                Navigation menu
                            </SheetTitle>
                            <SheetHeader className="text-start">
                                <AppLogo />
                            </SheetHeader>
                            <div className="flex flex-col gap-6 p-4">
                                {groups.map((group) => (
                                    <div
                                        key={group.label ?? '__top-level'}
                                        className="flex flex-col gap-1"
                                    >
                                        {group.label !== null && (
                                            <p className="px-2 text-xs font-medium text-muted-foreground">
                                                {group.label}
                                            </p>
                                        )}
                                        {group.items.map((item) => {
                                            const ItemIcon = resolveNavIcon(
                                                item.icon,
                                            );

                                            const itemClassName = cn(
                                                'flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-medium hover:bg-accent hover:text-accent-foreground',
                                                isCurrentUrl(item.href)
                                                    ? 'bg-accent text-accent-foreground'
                                                    : 'text-muted-foreground',
                                            );

                                            return item.external ? (
                                                <a
                                                    key={item.routeName}
                                                    href={item.href}
                                                    className={itemClassName}
                                                >
                                                    <ItemIcon className="size-4" />
                                                    <span>{item.label}</span>
                                                </a>
                                            ) : (
                                                <Link
                                                    key={item.routeName}
                                                    href={item.href}
                                                    prefetch
                                                    className={itemClassName}
                                                >
                                                    <ItemIcon className="size-4" />
                                                    <span>{item.label}</span>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                ))}
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                {homeItem ? (
                    <Link
                        href={homeItem.href}
                        prefetch
                        className="flex items-center"
                    >
                        <AppLogo />
                    </Link>
                ) : (
                    <AppLogo />
                )}

                {/* Desktop navigation */}
                <nav className="ms-6 hidden items-center gap-1 lg:flex">
                    {groups.map((group) =>
                        group.label === null ? (
                            group.items.map((item) => (
                                <Button
                                    key={item.routeName}
                                    asChild
                                    variant="ghost"
                                    size="sm"
                                    className={cn(
                                        isCurrentUrl(item.href)
                                            ? 'bg-accent text-accent-foreground'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {item.external ? (
                                        <a href={item.href}>{item.label}</a>
                                    ) : (
                                        <Link href={item.href} prefetch>
                                            {item.label}
                                        </Link>
                                    )}
                                </Button>
                            ))
                        ) : (
                            <DropdownMenu key={group.label}>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className={cn(
                                            group.items.some((item) =>
                                                isCurrentUrl(item.href),
                                            )
                                                ? 'text-accent-foreground'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {group.label}
                                        <ChevronDown className="size-3" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start">
                                    <DropdownMenuLabel>
                                        {group.label}
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    {group.items.map((item) => {
                                        const ItemIcon = resolveNavIcon(
                                            item.icon,
                                        );

                                        return (
                                            <DropdownMenuItem
                                                key={item.routeName}
                                                asChild
                                            >
                                                {item.external ? (
                                                    <a href={item.href}>
                                                        <ItemIcon />
                                                        <span>
                                                            {item.label}
                                                        </span>
                                                    </a>
                                                ) : (
                                                    <Link
                                                        href={item.href}
                                                        prefetch
                                                    >
                                                        <ItemIcon />
                                                        <span>
                                                            {item.label}
                                                        </span>
                                                    </Link>
                                                )}
                                            </DropdownMenuItem>
                                        );
                                    })}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ),
                    )}
                </nav>

                <div className="ms-auto flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        className="text-muted-foreground"
                        onClick={onOpenCommandPalette}
                    >
                        <Search />
                        <span className="hidden sm:inline">Search</span>
                        <kbd className="pointer-events-none hidden text-xs select-none sm:inline">
                            Ctrl K
                        </kbd>
                    </Button>
                    <ThemeSettingsMenu />
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                className="size-10 rounded-full p-1"
                            >
                                <Avatar className="size-8 overflow-hidden rounded-full">
                                    <AvatarImage
                                        src={auth.user.avatar}
                                        alt={auth.user.name}
                                    />
                                    <AvatarFallback className="rounded-lg bg-muted text-muted-foreground">
                                        {getInitials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="w-56" align="end">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            {breadcrumbs.length > 0 && (
                <div className="flex h-12 items-center border-t border-sidebar-border/50 px-6 md:px-4">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            )}
        </header>
    );
}
