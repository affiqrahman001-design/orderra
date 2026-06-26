<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Branch extends Model
{
    protected $fillable = [
        'public_id',
        'code',
        'name',
        'status',
        'country_code',
        'currency',
        'timezone',
        'phone',
        'email',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'supports_delivery',
        'supports_pickup',
        'supports_dine_in',
        'is_default',
        'meta',
    ];

    protected $casts = [
        'supports_delivery' => 'boolean',
        'supports_pickup' => 'boolean',
        'supports_dine_in' => 'boolean',
        'is_default' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $branch): void {
            if (! $branch->public_id) {
                $branch->public_id = (string) Str::uuid();
            }
        });
    }
}
