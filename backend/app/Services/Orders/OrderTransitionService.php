<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderTransitionService
{
    public function transition(
        Order $order,
        string $toStatus,
        ?string $reason = null,
        array $meta = [],
        string $actorType = 'admin',
        ?int $actorId = null,
    ): Order {
        if ($order->status === $toStatus) {
            throw ValidationException::withMessages([
                'to_status' => 'Order sudah berada pada status ini.',
            ]);
        }

        if (! in_array($toStatus, $order->allowedTransitions(), true)) {
            throw ValidationException::withMessages([
                'to_status' => 'Transition status tidak dibenarkan untuk fulfillment ini.',
            ]);
        }

        return DB::transaction(function () use ($order, $toStatus, $reason, $meta, $actorType, $actorId): Order {
            $fromStatus = $order->status;

            $payload = [
                'status' => $toStatus,
            ];

            if ($toStatus === 'completed') {
                $payload['completed_at'] = now();
            }

            if ($toStatus === 'cancelled') {
                $payload['cancelled_at'] = now();
            }

            $order->update($payload);

            $order->statusHistory()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_type' => $actorType,
                'changed_by_id' => $actorId,
                'reason' => $reason,
                'meta' => $meta,
            ]);

            return $order->fresh(['items', 'fulfillment', 'statusHistory']);
        });
    }
}
