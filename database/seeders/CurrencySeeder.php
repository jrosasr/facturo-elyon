<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Currency::create([
            'symbol' => 'Bs',
            'code' => 'VES',
            'name' => 'Bolívar Venezolano',
            'available' => true,
            'decimal_places' => 2,
            'decimal_separator' => ',',
            'thousands_separator' => '.',
        ]);

        Currency::create([
            'symbol' => '$',
            'code' => 'USD',
            'name' => 'Dólar Estadounidense',
            'available' => true,
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ]);

        // Peso colombiano
        Currency::create([
            'symbol' => '$',
            'code' => 'COP',
            'name' => 'Peso Colombiano',
            'available' => true,
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ]);

        // Peso Chileno
        Currency::create([
            'symbol' => '$',
            'code' => 'CLP',
            'name' => 'Peso Chileno',
            'available' => true,
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ]);
    }
}
