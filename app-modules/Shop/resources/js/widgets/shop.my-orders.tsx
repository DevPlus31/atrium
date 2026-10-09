import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormatters } from '@/hooks/use-formatters';
import { orderStatuses } from '../components/order-columns';

type MemberOrdersWidgetProps = {
    data: Modules.Shop.Data.MemberOrdersWidgetData;
};

export default function MemberOrdersWidget({ data }: MemberOrdersWidgetProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const format = useFormatters();

    return (
        <Card>
            <CardHeader>
                <CardDescription>{t('Your orders')}</CardDescription>
                <CardTitle className="text-3xl tabular-nums">
                    {data.total}
                </CardTitle>
            </CardHeader>
            <CardContent>
                {data.orders.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t("You haven't placed any orders yet.")}
                    </p>
                ) : (
                    <ul className="flex flex-col divide-y">
                        {data.orders.map((order) => {
                            const status = orderStatuses[order.status];

                            return (
                                <li
                                    key={order.id}
                                    className="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0"
                                >
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate font-mono text-sm">
                                            {order.number}
                                        </span>
                                        <span className="block text-xs text-muted-foreground">
                                            {format.date(order.placed_at)}
                                        </span>
                                    </span>
                                    <Badge variant={status.variant}>
                                        {t(status.label)}
                                    </Badge>
                                    <span className="text-sm tabular-nums">
                                        {format.money(
                                            order.total_cents,
                                            order.currency,
                                        )}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                )}
                {data.total > data.orders.length && (
                    <p className="mt-3 text-xs text-muted-foreground">
                        {tChoice(
                            ':count more order|:count more orders',
                            data.total - data.orders.length,
                        )}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
