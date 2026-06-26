<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ModifierGroup extends Model
{
    protected $fillable = [
        'public_id',
        'menu_item_id',
        'code',
        'name',
        'helper_text',
        'selection_mode',
        'is_required',
        'min_select',
        'max_select',
        'sort_order',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ModifierOption::class)->orderBy('sort_order')->orderBy('id');
    }
}
