import { Head, Link, router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, DataTableToolbar } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useTableState } from '@/hooks/use-table-state';
import AdminLayout from '@/layouts/admin-layout';
import { create, destroy, index, publish } from '@/routes/admin/products';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/admin';
import type { ProductRow } from '../components/product-columns';
import { buildProductColumns } from '../components/product-columns';

type ProductsIndexProps = {
    products: Paginated<ProductRow>;
};

export default function ProductsIndex({ products }: ProductsIndexProps) {
    const { t } = useLaravelReactI18n();
    const tableState = useTableState('products');
    const [pendingDelete, setPendingDelete] = useState<ProductRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Products'), href: index() },
    ];

    const publishProduct = (product: ProductRow) => {
        router.post(publish.url(product.id), {}, { preserveScroll: true });
    };

    const columns = buildProductColumns(t, publishProduct, setPendingDelete);

    const confirmDelete = () => {
        if (pendingDelete === null) {
            return;
        }

        router.delete(destroy.url(pendingDelete.id), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Products')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search products...')}
                actions={
                    <Button size="sm" asChild>
                        <Link href={create()}>
                            <Plus className="size-4" />
                            {t('Create product')}
                        </Link>
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                paginated={products}
                tableState={tableState}
                emptyMessage={t('No products found.')}
            />
            <ConfirmDialog
                open={pendingDelete !== null}
                onOpenChange={(open) => {
                    if (!open && !deleting) {
                        setPendingDelete(null);
                    }
                }}
                title={t('Delete product')}
                description={t(
                    'This will permanently delete :name and cannot be undone.',
                    { name: pendingDelete?.name ?? t('this product') },
                )}
                confirmLabel={t('Delete')}
                processing={deleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
