<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MenuItem extends Model
{
    protected $fillable = [
        'public_id',
        'branch_id',
        'menu_category_id',
        'code',
        'name',
        'short_name',
        'slug',
        'description',
        'base_price_amount',
        'currency',
        'image_url',
        'is_active',
        'is_featured',
        'badge_label',
        'prep_note',
        'product_flow',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'meta' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function modifierGroups(): HasMany
    {
        return $this->hasMany(ModifierGroup::class)->orderBy('sort_order')->orderBy('id');
    }
}
