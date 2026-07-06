import { Deferred, Head, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ReactNode } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import AdminLayout from '@/layouts/admin-layout';
import { index } from '@/routes/admin/dashboard';
import type { BreadcrumbItem } from '@/types';
import RecentUsersWidget from '../components/widgets/recent-users';
import UsersTotalWidget from '../components/widgets/users-total';

type WidgetDescriptor = Modules.Dashboard.Data.WidgetDescriptorData;

type DashboardIndexProps = {
    widgets: WidgetDescriptor[];
};

/**
 * Maps registry widget keys to their components; unknown keys render
 * nothing so a module can register widgets before shipping a frontend.
 */
const widgetRenderers: Record<string, (data: unknown) => ReactNode> = {
    'users.total': (data) => (
        <UsersTotalWidget
            data={data as Modules.Users.Data.UsersTotalWidgetData}
        />
    ),
    'users.recent': (data) => (
        <RecentUsersWidget
            data={data as Modules.Users.Data.RecentUsersWidgetData}
        />
    ),
};

function WidgetSkeleton() {
    return (
        <div className="flex flex-col gap-3 rounded-xl border bg-card p-6">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-8 w-32" />
            <Skeleton className="h-32 w-full" />
        </div>
    );
}

function DeferredWidget({ descriptor }: { descriptor: WidgetDescriptor }) {
    const { props } = usePage();
    const render = widgetRenderers[descriptor.key];

    if (!render) {
        return null;
    }

    const data: unknown = props[`widget:${descriptor.key}`];

    return (
        <Deferred
            data={`widget:${descriptor.key}`}
            fallback={<WidgetSkeleton />}
        >
            {data === undefined || data === null ? (
                <WidgetSkeleton />
            ) : (
                <>{render(data)}</>
            )}
        </Deferred>
    );
}

export default function DashboardIndex({ widgets }: DashboardIndexProps) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Dashboard'), href: index() },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Dashboard')} />
            <div className="grid auto-rows-min gap-4 md:grid-cols-2 xl:grid-cols-3">
                {widgets.map((descriptor) => (
                    <DeferredWidget
                        key={descriptor.key}
                        descriptor={descriptor}
                    />
                ))}
            </div>
            {widgets.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    {t('No widgets available.')}
                </p>
            )}
        </AdminLayout>
    );
}
