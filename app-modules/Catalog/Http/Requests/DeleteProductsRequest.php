<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests;

use App\Modules\Concerns\ValidatesBulkSelection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalog\Infrastructure\Models\Product;

final class DeleteProductsRequest extends FormRequest
{
    use ValidatesBulkSelection;

    /**
     * The selected products the user may delete.
     *
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return $this->permitted(Product::query(), 'delete');
    }
}
