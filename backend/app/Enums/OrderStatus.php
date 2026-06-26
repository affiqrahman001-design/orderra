<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case CartDraft = 'cart_draft';
    case PendingPayment = 'pending_payment';
    case PaymentAuthorized = 'payment_authorized';
    case Placed = 'placed';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';

    case AwaitingRider = 'awaiting_rider';
    case RiderAssigned = 'rider_assigned';
    case PickedUp = 'picked_up';
    case NearCustomer = 'near_customer';
    case Delivered = 'delivered';

    case ReadyForPickup = 'ready_for_pickup';
    case PickedUpByCustomer = 'picked_up_by_customer';

    case Served = 'served';
    case BillRequested = 'bill_requested';
    case PaidAtTable = 'paid_at_table';

    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case RefundPending = 'refund_pending';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
