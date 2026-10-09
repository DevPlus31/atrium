<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Repositories;

use App\Domain\Contracts\Repository;
use Modules\Shop\Infrastructure\Models\Order;

interface OrderRepository extends Repository
{
    /**
     * Re-read the order inside the current transaction, locking its row so
     * concurrent workflow changes are checked against the latest state.
     */
    public function lockForUpdate(Order $order): Order;

    public function save(Order $order): void;

    public function delete(Order $order): void;
}
