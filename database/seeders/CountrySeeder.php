<?php
namespace Database\Seeders;

use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::firstOrCreate([
            'name'          => 'Malaysia',
            'currency_code' => 'MYR',
            'is_active'     => 1
        ]);
    }
}
