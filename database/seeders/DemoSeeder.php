<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Payment;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use App\Services\HouseOccupancyService;
use Illuminate\Database\Seeder;

/**
 * Optional demo data: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(HouseOccupancyService::class);

        House::factory(8)->create()->each(function (House $house) use ($service) {
            if (fake()->boolean(60)) {
                $tenant = Tenant::factory()->create();
                $service->assign($house, $tenant);

                RentalAgreement::factory()->create([
                    'tenant_id' => $tenant->id,
                    'house_id' => $house->id,
                    'monthly_rent' => $house->rent_amount,
                ]);

                Payment::factory(rand(1, 4))->create([
                    'tenant_id' => $tenant->id,
                    'amount_paid' => $house->rent_amount,
                ]);
            }
        });
    }
}
