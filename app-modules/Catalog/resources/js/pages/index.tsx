import { Head, Link, router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Plus } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DataTable,
    DataTableBulkActions,
    DataTableToolbar,
} from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useBulkDelete } from '@/hooks/use-bulk-delete';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import { useRowSelection } from '@/hooks/use-row-selection';
import { useTableState } from '@/hooks/use-table-state';
import {
    bulkDestroy,
    create,
    destroy,
    index,
    publish,
} from '@/routes/admin/products';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/admin';
import type { ProductRow } from '../components/product-columns';
import { buildProductColumns } from '../components/product-columns';

type ProductsIndexProps = {
    products: Paginated<ProductRow>;
    can: { create: boolean };
};

export default function ProductsIndex({ products, can }: ProductsIndexProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const format = useFormatters();
    const tableState = useTableState('products');
    const deleteDialog = useDeleteDialog<ProductRow>((row) =>
        destroy.url(row.id),
    );
    const selection = useRowSelection(
        products.data,
        (row) => row.id,
        (row) => row.can.delete,
    );
    const bulkDelete = useBulkDelete(bulkDestroy.url(), selection);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Products'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    const publishProduct = (product: ProductRow) => {
        router.post(publish.url(product.id), {}, { preserveScroll: true });
    };

    const columns = buildProductColumns(
        t,
        format,
        publishProduct,
        deleteDialog.request,
    );

    return (
        <>
            <Head title={t('Products')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search products...')}
                actions={
                    can.create && (
                        <Button size="sm" asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                {t('Create product')}
                            </Link>
                        </Button>
                    )
                }
            />
            <DataTableBulkActions selection={selection}>
                <Button
                    variant="destructive"
                    size="sm"
                    onClick={bulkDelete.request}
                    data-test="bulk-delete"
                >
                    {t('Delete selected')}
                </Button>
            </DataTableBulkActions>
            <DataTable
                selection={selection}
                columns={columns}
                paginated={products}
                tableState={tableState}
                emptyMessage={t('No products found.')}
            />
            <ConfirmDialog
                {...bulkDelete.dialogProps}
                title={t('Delete selected products')}
                description={tChoice(
                    'This will permanently delete :count product and cannot be undone.|This will permanently delete :count products and cannot be undone.',
                    selection.selectedIds.length,
                )}
                confirmLabel={t('Delete')}
            />
            <ConfirmDialog
                {...deleteDialog.dialogProps}
                title={t('Delete product')}
                description={t(
                    'This will permanently delete :name and cannot be undone.',
                    { name: deleteDialog.pending?.name ?? t('this product') },
                )}
                confirmLabel={t('Delete')}
            />
        </>
    );
}
