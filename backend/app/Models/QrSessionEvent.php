<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class QrSessionEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'qr_session_id',
        'order_id',
        'cart_id',
        'event_type',
        'actor_type',
        'actor_id',
        'note',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function qrSession(): BelongsTo
    {
        return $this->belongsTo(QrSession::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
