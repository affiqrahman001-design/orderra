<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModifierOption extends Model
{
    protected $fillable = [
        'public_id',
        'modifier_group_id',
        'code',
        'label',
        'price_delta_amount',
        'is_default',
        'sort_order',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class);
    }
}
