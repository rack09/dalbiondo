<?php

namespace Database\Seeders;

use App\Models\Pizza;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\User;

class DalBiondoPizzaSeeder extends Seeder
{
    public function run(): void
    {
        $pizzas = [
            ['Dal Biondo', 'Pomodoro, mozzarella, gorgonzola e salsiccia dolce.', '6.00', '36.00'],
            ['Margherita', 'Pomodoro e mozzarella.', '4.00', '24.00'],
            ['Diavola', 'Pomodoro, mozzarella e salame piccante.', '5.50', '33.00'],
            ['Fantasia', 'Pomodoro, mozzarella, prosciutto e funghi.', '5.00', '30.00'],
            ['Ortolana', 'Pomodoro, mozzarella, zucchine, peperoni e melanzane.', '6.00', '36.00'],
            ['Friarielli', 'Pomodoro, mozzarella, salsiccia dolce e friarielli.', '6.50', '39.00'],
            ['4 Formaggi', 'Pomodoro, mozzarella, taleggio, gorgonzola e scamorza.', '6.00', '36.00'],
            ['4 Stagioni', 'Pomodoro, mozzarella, prosciutto, funghi, olive e carciofi.', '6.00', '36.00'],
            ['Baita', 'Pomodoro, mozzarella, funghi e salsiccia dolce.', '5.50', '33.00'],
            ['Capricciosa', 'Pomodoro, mozzarella, olive, carciofi, funghi e acciughe.', '6.00', '36.00'],
            ['Crudo', 'Pomodoro, mozzarella e prosciutto crudo.', '6.00', '36.00'],
            ['Focaccia', 'Pomodoro, mozzarella, grana, prosciutto crudo e rucola.', '7.50', '45.00'],
            ['Golosa', 'Pomodoro, mozzarella, prosciutto, funghi, gorgonzola, wurstel e salsiccia dolce.', '7.50', '45.00'],
            ['Grana e Rucola', 'Pomodoro, mozzarella, grana e rucola.', '5.50', '33.00'],
            ['Marinara', 'Pomodoro, aglio e origano.', '3.50', '21.00'],
            ['Napoli', 'Pomodoro, mozzarella, acciughe e origano.', '5.00', '30.00'],
            ['Patatina', 'Pomodoro, mozzarella e patatine.', '5.00', '30.00'],
            ['Pugliese', 'Pomodoro, mozzarella e cipolla.', '5.00', '30.00'],
            ['Romana', 'Pomodoro, mozzarella, capperi, acciughe e origano.', '5.50', '33.00'],
            ['Rossonera', 'Pomodoro, mozzarella, salame piccante, gorgonzola e peperoni.', '7.50', '45.00'],
            ['Siciliana', 'Pomodoro, mozzarella, acciughe, capperi, olive e salame piccante.', '6.50', '39.00'],
            ['Speck', 'Pomodoro, mozzarella e speck.', '6.00', '36.00'],
            ['Tonno', 'Pomodoro, mozzarella e tonno.', '5.50', '33.00'],
            ['Wurstel', 'Pomodoro, mozzarella e wurstel.', '5.00', '30.00'],
        ];

        DB::transaction(function () use ($pizzas): void {
            User::query()->where('role', 'owner')->lockForUpdate()->first(['id']);

            $applyDefaultSelection = Pizza::query()
                ->where('is_featured', true)
                ->doesntExist();

            $featuredNames = ['Dal Biondo', 'Diavola', 'Margherita'];

            foreach ($pizzas as [$name, $ingredients, $priceTrancio, $priceTeglia]) {
                $pizza = Pizza::query()->firstOrCreate(
                    ['name' => $name],
                    [
                        'ingredients' => $ingredients,
                        'price_trancio' => $priceTrancio,
                        'price_teglia' => $priceTeglia,
                        'is_visible' => true,
                    ],
                );

                if ($applyDefaultSelection && in_array($name, $featuredNames, true)) {
                    Pizza::query()
                        ->whereKey($pizza->id)
                        ->where('is_visible', true)
                        ->update([
                            'is_featured' => true,
                            'is_house_pizza' => $name === 'Dal Biondo',
                        ]);
                }
            }
        }, 3);
    }
}
