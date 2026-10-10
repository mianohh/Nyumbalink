<?php

namespace App\Models;

use App\Enums\HouseStatus;
use Database\Factories\HouseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class House extends Model
{
    /** @use HasFactory<HouseFactory> */
    use HasFactory;

    protected $fillable = [
        'house_number',
        'location',
        'rent_amount',
        'status',
        'description',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'rent_amount' => 'decimal:2',
            'status' => HouseStatus::class,
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(RentalAgreement::class);
    }

    public function currentTenant(): ?Tenant
    {
        return $this->tenants()->first();
    }
}
