<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The menu registry. One row per navigable module, holding both what the
 * sidebar renders and which route names the row governs — so a single mapping
 * decides visibility and access instead of the two drifting apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('menu_code', 50)->unique();
            $table->string('menu_name', 100);
            $table->string('icon', 50)->nullable();
            $table->string('group_name', 50)->nullable()->comment('Sidebar heading; null = ungrouped top block');

            // The link the sidebar points at.
            $table->string('route_name', 100);

            // Every route name this menu governs, as Str::is() patterns. The
            // middleware picks the most specific match, so "attendance.*" and
            // "attendance.monitoring" can coexist as separate menus.
            $table->json('route_patterns');

            // Menus whose page needs users.employee_id (the check-in screen).
            // Kept as data so the sidebar has no special case in Blade.
            $table->boolean('requires_employee')->default(false);

            // Locked menus stay reachable for ADMIN whatever the mapping says —
            // without this, unchecking Role from ADMIN would remove the only
            // screen that can put it back.
            $table->boolean('is_locked')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE / INACTIVE');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
