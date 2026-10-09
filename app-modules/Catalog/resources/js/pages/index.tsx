import { Head, router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    DataTable,
    DataTableBulkDelete,
    DataTableCreateButton,
    DataTableDeleteDialog,
    DataTableFacetedFilter,
    DataTableToolbar,
} from '@/components/data-table';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useFormatters } from '@/hooks/use-formatters';
import { useResourceTable } from '@/hooks/use-resource-table';
import {
    bulkDestroy,
    create,
    destroy,
    index,
    publish,
} from '@/routes/admin/products';
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
    const { tableState, deleteDialog, selection, bulkDelete } =
        useResourceTable({
            key: 'products',
            rows: products.data,
            destroyUrl: (row) => destroy.url(row.id),
            bulkDestroyUrl: bulkDestroy.url(),
        });

    useBreadcrumbs({ title: t('Products'), href: index() });

    const columns = buildProductColumns(
        t,
        format,
        (product) =>
            router.post(publish.url(product.id), {}, { preserveScroll: true }),
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
                        <DataTableCreateButton
                            href={create()}
                            label={t('Create product')}
                        />
                    )
                }
            >
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="status"
                    title={t('Status')}
                    options={[
                        { label: t('Draft'), value: 'draft' },
                        { label: t('Published'), value: 'published' },
                    ]}
                />
            </DataTableToolbar>
            <DataTableBulkDelete
                selection={selection}
                bulkDelete={bulkDelete}
                title={t('Delete selected products')}
                description={(count) =>
                    tChoice(
                        'This will permanently delete :count product and cannot be undone.|This will permanently delete :count products and cannot be undone.',
                        count,
                    )
                }
            />
            <DataTable
                selection={selection}
                columns={columns}
                paginated={products}
                tableState={tableState}
                emptyMessage={t('No products found.')}
            />
            <DataTableDeleteDialog
                dialog={deleteDialog}
                title={t('Delete product')}
                name={(product) => product.name}
                fallbackName={t('this product')}
            />
        </>
    );
}
