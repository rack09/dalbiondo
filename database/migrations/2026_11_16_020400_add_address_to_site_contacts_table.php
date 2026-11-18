<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_contacts', function (Blueprint $table) {
            $table->string('street_address', 160)->default('Via Sant’Antonio 13');
            $table->string('postal_code', 10)->default('20092');
            $table->string('city', 120)->default('Cinisello Balsamo');
            $table->string('province', 2)->default('MI');
        });
    }

    public function down(): void
    {
        Schema::table('site_contacts', function (Blueprint $table) {
            $table->dropColumn(['street_address', 'postal_code', 'city', 'province']);
        });
    }
};
