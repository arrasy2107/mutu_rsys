<?php

namespace Database\Seeders;

use App\Models\IndicatorCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            IndicatorCategorySeeder::class,
            UserSeeder::class,
            IndicatorSeeder::class,
        ]);
    }
}
