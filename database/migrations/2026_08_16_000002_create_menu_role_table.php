<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The mapping itself: a row here means "this role may reach this menu".
 * Absence is denial, so revoking access is a delete rather than a flag — there
 * is no second place where a stale "allowed = 0" row could contradict it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['menu_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_role');
    }
};
