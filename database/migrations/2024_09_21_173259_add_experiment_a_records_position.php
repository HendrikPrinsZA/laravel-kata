<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiment_a_records', function (Blueprint $table) {
            $table->unsignedSmallInteger('position')->default(0)->after('id');

            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::table('experiment_a_records', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
