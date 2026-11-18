<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PHOTOS = [
        [
            'staff-storico.webp',
            'Foto storica dello staff',
            'Tre persone dietro il banco, davanti al forno della pizzeria.',
            1350,
            1080,
        ],
        [
            'staff-archivio-2.webp',
            'Ricordi dietro il banco',
            'Quattro persone dietro il banco, con arancini e panzerotti in vetrina.',
            1024,
            768,
        ],
        [
            'staff-archivio-3.webp',
            'Volti di Dal Biondo',
            'Due persone dietro il banco, con le scatole della pizza sullo sfondo.',
            768,
            1024,
        ],
    ];

    public function up(): void
    {
        $now = now();
        $rows = [];

        foreach (self::PHOTOS as $index => [$file, $caption, $alt, $width, $height]) {
            $rows[] = [
                'id' => $index + 1,
                'path' => 'dal-biondo/'.$file,
                'is_static' => true,
                'caption' => $caption,
                'alt' => $alt,
                'width' => $width,
                'height' => $height,
                'is_visible' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('staff_photos')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        DB::table('staff_photos')->where('is_static', true)->delete();
    }
};
