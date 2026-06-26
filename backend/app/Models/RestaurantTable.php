<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class RestaurantTable extends Model
{
    protected $fillable = [
        'public_id',
        'branch_id',
        'code',
        'label',
        'seat_capacity',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function qrSessions(): HasMany
    {
        return $this->hasMany(QrSession::class);
    }

    public function latestActiveQrSession(): HasOne
    {
        return $this->hasOne(QrSession::class)
            ->whereIn('status', (array) config('dine_in.qr_sessions.active_statuses', []))
            ->latestOfMany();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
