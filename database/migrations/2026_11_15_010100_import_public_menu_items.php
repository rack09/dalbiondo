<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ITEMS = [
        ['rotonde', 'Margherita', 'Pomodoro e mozzarella.', null],
        ['rotonde', 'Diavola', 'Pomodoro, mozzarella e salame piccante.', null],
        ['rotonde', 'Biondo', 'Pomodoro, mozzarella, gorgonzola e salsiccia dolce.', null],
        ['rotonde', 'Calzone', 'Pomodoro, mozzarella e prosciutto.', null],
        ['sfizi', 'Arancini', 'Un classico da aggiungere alla tua pizza.', null],
        ['sfizi', 'Panzerotti', 'Per una pausa piena di gusto.', null],
        ['sfizi', 'Focaccia', 'Liscia o ripiena.', null],
        ['sfizi', 'Patatine fritte', 'Da gustare insieme al tuo ordine.', null],
        ['bibite', 'Coca-Cola Original', '45 cl', '2.00'],
        ['bibite', 'Coca-Cola Zero Zuccheri', '45 cl', '2.00'],
        ['bibite', 'Acqua naturale', '50 cl', '1.00'],
        ['bibite', 'Acqua frizzante', '50 cl', '1.00'],
        ['bibite', 'Estathè al limone', '50 cl', '2.00'],
        ['bibite', 'Estathè alla pesca', '50 cl', '2.00'],
        ['bibite', 'Moretti', 'Birra · 33 cl', '2.00'],
        ['bibite', 'Beck’s', 'Birra · 33 cl', '2.50'],
        ['bibite', 'Heineken', 'Birra · 33 cl', '2.50'],
    ];

    public function up(): void
    {
        $now = now();
        $rows = [];

        foreach (self::ITEMS as $index => [$category, $name, $description, $price]) {
            $rows[] = [
                'id' => $index + 1,
                'category' => $category,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'is_visible' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('menu_items')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        DB::table('menu_items')->whereIn('id', range(1, count(self::ITEMS)))->delete();
    }
};
