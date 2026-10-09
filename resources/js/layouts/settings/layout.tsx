import { Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { LucideIcon } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editNotificationPreferences } from '@/routes/notification-preferences';
import { show as showPasskeys } from '@/routes/passkeys';
import { edit as editPassword } from '@/routes/password';
import { index as sessions } from '@/routes/sessions';
import { show as showTwoFactor } from '@/routes/two-factor';
import { edit } from '@/routes/user-profile';

type SettingsNavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon | null;
};

const sidebarNavItems: SettingsNavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Password',
        href: editPassword(),
        icon: null,
    },
    {
        title: 'Two-Factor Auth',
        href: showTwoFactor(),
        icon: null,
    },
    {
        title: 'Passkeys',
        href: showPasskeys(),
        icon: null,
    },
    {
        title: 'Sessions',
        href: sessions(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
    {
        title: 'Notifications',
        href: editNotificationPreferences(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { t } = useLaravelReactI18n();
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { settingsNav } = usePage().props;

    // The account pages the shell ships, then any a module adds (labels
    // arrive translated, via the navigation registry's settings area).
    const items: SettingsNavItem[] = [
        ...sidebarNavItems.map((item) => ({ ...item, title: t(item.title) })),
        ...settingsNav.map((item) => ({
            title: item.label,
            href: item.href,
            icon: null,
        })),
    ];

    return (
        <div className="px-4 py-6">
            <Heading
                title={t('Settings')}
                description={t('Manage your profile and account settings')}
            />

            <div className="flex flex-col gap-6 lg:flex-row lg:gap-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col gap-1"
                        aria-label={t('Settings')}
                    >
                        {items.map((item) => (
                            <Button
                                key={toUrl(item.href)}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
