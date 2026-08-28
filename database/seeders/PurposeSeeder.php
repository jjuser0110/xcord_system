<?php

namespace Database\Seeders;

use App\Models\Purpose;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $openingBalance = Purpose::updateOrCreate(
            ['title' => 'Opening Balance'],
            [
                'description' => 'Initial capital entry for bank accounts',
                'is_global'   => true,
                'is_active'   => 1,
            ]
        );
        $openingBalance->countries()->detach();

        $manualAdjust = Purpose::updateOrCreate(
            ['title' => 'Manual Adjust'],
            [
                'description' => 'Manual balance correction or adjustment',
                'is_global'   => true,
                'is_active'   => 1,
            ]
        );
        $manualAdjust->countries()->detach();
    }
}
