import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
} from 'recharts';
import type { TooltipContentProps } from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type UsersTotalWidgetProps = {
    data: Modules.Users.Data.UsersTotalWidgetData;
};

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
});

const numberFormatter = new Intl.NumberFormat();

function formatDate(date: string): string {
    return dateFormatter.format(new Date(`${date}T00:00:00`));
}

function SeriesTooltip({ active, payload, label }: TooltipContentProps) {
    const { tChoice } = useLaravelReactI18n();

    if (!active || payload.length === 0) {
        return null;
    }

    const count = Number(payload[0]?.value ?? 0);

    return (
        <div className="rounded-md border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md">
            <p className="font-medium">{formatDate(String(label))}</p>
            <p className="text-muted-foreground">
                {tChoice(':count new user|:count new users', count, {
                    count: numberFormatter.format(count),
                })}
            </p>
        </div>
    );
}

export default function UsersTotalWidget({ data }: UsersTotalWidgetProps) {
    const { t } = useLaravelReactI18n();

    return (
        <Card>
            <CardHeader>
                <CardDescription>{t('Total users')}</CardDescription>
                <CardTitle className="text-3xl tabular-nums">
                    {numberFormatter.format(data.total)}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="h-32 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart
                            data={data.series}
                            margin={{ top: 4, right: 4, bottom: 0, left: 4 }}
                        >
                            <CartesianGrid
                                stroke="var(--border)"
                                strokeDasharray="3 3"
                                vertical={false}
                            />
                            <XAxis
                                dataKey="date"
                                tickFormatter={formatDate}
                                tick={{
                                    fill: 'var(--muted-foreground)',
                                    fontSize: 11,
                                }}
                                tickLine={false}
                                axisLine={{ stroke: 'var(--border)' }}
                                interval="preserveStartEnd"
                                minTickGap={32}
                            />
                            <Tooltip
                                content={SeriesTooltip}
                                cursor={{ stroke: 'var(--border)' }}
                                isAnimationActive={false}
                            />
                            <Area
                                type="monotone"
                                dataKey="count"
                                stroke="var(--chart-1)"
                                strokeWidth={2}
                                fill="var(--chart-1)"
                                fillOpacity={0.15}
                                isAnimationActive={false}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
                <p className="mt-2 text-xs text-muted-foreground">
                    {t('New users over the last 14 days')}
                </p>
            </CardContent>
        </Card>
    );
}
