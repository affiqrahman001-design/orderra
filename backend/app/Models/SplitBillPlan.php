<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SplitBillPlan extends Model
{
    protected $fillable = [
        'public_id',
        'qr_session_id',
        'split_type',
        'status',
        'currency',
        'session_totals_snapshot',
        'rules_snapshot',
        'meta',
        'finalized_at',
        'cancelled_at',
    ];

    protected $casts = [
        'session_totals_snapshot' => 'array',
        'rules_snapshot' => 'array',
        'meta' => 'array',
        'finalized_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function qrSession(): BelongsTo
    {
        return $this->belongsTo(QrSession::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SplitBillParticipant::class)->orderBy('participant_order');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SplitBillAllocation::class);
    }

    public function isLocked(): bool
    {
        return $this->status === 'finalized';
    }
}
