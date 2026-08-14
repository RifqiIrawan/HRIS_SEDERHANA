<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §12 — Master Lokasi. radius_meter defaults to 10 and
 * gps_accuracy_limit to 20; both are the only numbers the geofence trusts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_code', 30)->unique();
            $table->string('location_name', 150);
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('radius_meter')->default(10);
            $table->unsignedSmallInteger('gps_accuracy_limit')->default(20);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
