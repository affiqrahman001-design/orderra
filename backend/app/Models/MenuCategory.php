<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MenuCategory extends Model
{
    protected $fillable = [
        'public_id',
        'code',
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'menu_category_id');
    }
}
