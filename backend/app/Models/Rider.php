<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Rider extends Model
{
    protected $fillable = [
        'public_id',
        'rider_code',
        'name',
        'phone',
        'type',
        'status',
        'vehicle_type',
        'meta',
        'is_demo',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_demo' => 'boolean',
    ];

    public function deliveryAssignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class)->latest('id');
    }
}
