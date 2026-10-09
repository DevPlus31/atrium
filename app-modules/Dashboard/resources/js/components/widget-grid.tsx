import { Deferred, usePage } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { Skeleton } from '@/components/ui/skeleton';

type WidgetDescriptor = Modules.Dashboard.Data.WidgetDescriptorData;

type WidgetComponent = ComponentType<{ data: unknown }>;

/**
 * Widget components shipped by modules as
 * `app-modules/<Module>/resources/js/widgets/<widget key>.tsx`, keyed by the
 * registry key. Unknown keys render nothing, so a module can register a
 * widget before shipping its component.
 */
const widgetComponents: Record<string, WidgetComponent> = Object.fromEntries(
    Object.entries(
        import.meta.glob<{ default: WidgetComponent }>(
            '../../../../*/resources/js/widgets/*.tsx',
            { eager: true },
        ),
    ).map(([path, module]) => [
        path.slice(path.lastIndexOf('/') + 1, -'.tsx'.length),
        module.default,
    ]),
);

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
    const Widget = widgetComponents[descriptor.key];

    if (Widget === undefined) {
        return null;
    }

    const data: unknown = props[descriptor.prop];

    // Undefined: still loading. Null: the widget has nothing for this user.
    return (
        <Deferred data={descriptor.prop} fallback={<WidgetSkeleton />}>
            {data === undefined ? (
                <WidgetSkeleton />
            ) : data === null ? null : (
                <Widget data={data} />
            )}
        </Deferred>
    );
}

/**
 * The responsive grid of a dashboard's widgets, each loading after the
 * first paint behind its own skeleton.
 */
export function WidgetGrid({
    widgets,
    empty,
}: {
    widgets: WidgetDescriptor[];
    empty: string;
}) {
    return (
        <>
            <div className="grid auto-rows-min gap-4 md:grid-cols-2 xl:grid-cols-3">
                {widgets.map((descriptor) => (
                    <DeferredWidget
                        key={descriptor.key}
                        descriptor={descriptor}
                    />
                ))}
            </div>
            {widgets.length === 0 && (
                <p className="text-sm text-muted-foreground">{empty}</p>
            )}
        </>
    );
}
