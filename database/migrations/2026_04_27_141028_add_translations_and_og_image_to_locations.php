<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('name_sr', 255)->nullable()->after('name');
            $table->string('address_sr', 500)->nullable()->after('address');
            $table->string('city_sr', 100)->nullable()->after('city');
            $table->string('meta_title_sr', 255)->nullable()->after('meta_title');
            $table->string('meta_description_sr', 500)->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['name_sr', 'address_sr', 'city_sr', 'meta_title_sr', 'meta_description_sr']);
        });
    }
};
