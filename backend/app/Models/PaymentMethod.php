<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethodCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'family',
        'kind',
        'is_active',
        'is_demo_enabled',
        'requires_intent',
        'supports_manual_simulation',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'code' => PaymentMethodCode::class,
            'is_active' => 'boolean',
            'is_demo_enabled' => 'boolean',
            'requires_intent' => 'boolean',
            'supports_manual_simulation' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDemoEnabled(Builder $query): Builder
    {
        return $query->where('is_demo_enabled', true);
    }

    public function isOffline(): bool
    {
        return $this->kind === 'offline';
    }

    public function isDigital(): bool
    {
        return $this->kind === 'digital';
    }
}
