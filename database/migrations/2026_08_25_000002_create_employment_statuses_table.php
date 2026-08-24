<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Status Kepegawaian" — the values behind employees.employment_status
 * (spec §11), which used to be a hard-coded PERCOBAAN/KONTRAK/TETAP list.
 *
 * The employee row keeps storing the code rather than a foreign key: the code
 * is what every existing row, export and report already carries, so promoting
 * the list to a table must not rewrite that column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Disimpan apa adanya di employees.employment_status');
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Rows the application logic itself names in PHP. Their code is
            // frozen and they cannot be deleted, because renaming one would
            // break a comparison no migration can find.
            $table->boolean('is_system')->default(false);

            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE / INACTIVE');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_statuses');
    }
};
