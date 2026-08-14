<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reverse-geocoded administrative area for each GPS reading.
 *
 * This is a human-readable label only — "Melawai, Kby. Baru, Jakarta Selatan".
 * It is never used to decide whether an attendance is valid; that stays with
 * GeofenceService and the coordinates already stored alongside it. Keeping it
 * descriptive is what lets the lookup fail (offline, rate-limited, timeout)
 * without blocking a check-in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_in_address')->nullable()->after('check_in_photo');
            $table->string('check_out_address')->nullable()->after('check_out_photo');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_address', 'check_out_address']);
        });
    }
};
