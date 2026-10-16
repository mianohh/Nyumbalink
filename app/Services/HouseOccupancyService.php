<?php

namespace App\Services;

use App\Enums\HouseStatus;
use App\Models\House;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the tenants.house_id <-> houses.status link.
 * All assignment/unassignment flows must go through this service.
 */
class HouseOccupancyService
{
    /**
     * Assign a tenant to a house, marking the house occupied.
     * The tenant's previous house (if any) is released.
     */
    public function assign(House $house, Tenant $tenant): void
    {
        DB::transaction(function () use ($house, $tenant) {
            $previous = Tenant::where('house_id', $house->id)
                ->whereKeyNot($tenant->id)
                ->lockForUpdate()
                ->get();

            Tenant::whereKey($tenant->id)->lockForUpdate()->first();

            $previous->each->update(['house_id' => null]);

            $tenant->update(['house_id' => $house->id]);

            $house->update(['status' => HouseStatus::Occupied]);
        });
    }

    /**
     * Unassign every tenant from the house and mark it available
     * (unless it is in maintenance).
     */
    public function unassign(House $house): void
    {
        DB::transaction(function () use ($house) {
            Tenant::where('house_id', $house->id)
                ->lockForUpdate()
                ->get()
                ->each->update(['house_id' => null]);

            if ($house->status !== HouseStatus::Maintenance) {
                $house->update(['status' => HouseStatus::Available]);
            }
        });
    }

    /**
     * Recompute a house's status based on current occupants.
     * Called after tenant create/update/delete flows.
     */
    public function refresh(House $house): void
    {
        $occupied = Tenant::where('house_id', $house->id)->exists();

        if ($house->status === HouseStatus::Maintenance) {
            return;
        }

        $house->update([
            'status' => $occupied ? HouseStatus::Occupied : HouseStatus::Available,
        ]);
    }
}
