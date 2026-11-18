<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->boolean('is_static')->default(false);
            $table->string('caption', 160);
            $table->string('alt', 200);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->boolean('is_visible')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_photos');
    }
};
