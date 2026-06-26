<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Str;

final class OrderCodeGenerator
{
    public function generate(): string
    {
        do {
            $candidate = sprintf(
                '%s-%s-%s',
                config('orders.placement.order_code_prefix', 'ORD'),
                now()->format('ymd'),
                Str::upper(Str::random(6)),
            );
        } while (Order::query()->where('order_code', $candidate)->exists());

        return $candidate;
    }
}
