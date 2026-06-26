<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentProviderCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'driver',
        'mode',
        'is_active',
        'live_enabled',
        'webhook_enabled',
        'supports_refunds',
        'settings',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'code' => PaymentProviderCode::class,
            'is_active' => 'boolean',
            'live_enabled' => 'boolean',
            'webhook_enabled' => 'boolean',
            'supports_refunds' => 'boolean',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isDemoMode(): bool
    {
        return $this->mode === 'demo';
    }

    public function allowsLiveExecution(): bool
    {
        return $this->live_enabled === true;
    }
}
