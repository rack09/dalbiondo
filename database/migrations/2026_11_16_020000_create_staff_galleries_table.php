<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_galleries', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        DB::table('staff_galleries')->insert([
            'id' => 1,
            'is_visible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_galleries');
    }
};
