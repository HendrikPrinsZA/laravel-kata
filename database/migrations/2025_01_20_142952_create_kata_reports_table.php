<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kata_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->nullable(false);
            $table->string('php_version', 8)->nullable(false);
            $table->string('laravel_version', 8)->nullable(false);
            $table->string('mode', 16)->nullable(false);
            $table->float('max_duration', 2)->nullable(false);
            $table->integer('max_iterations')->nullable(false);
            $table->string('class', 64)->nullable(false);
            $table->string('method', 128)->nullable(false);
            $table->string('phase', 1)->nullable(false);

            // Stats
            $table->bigInteger('max_duration_iterations')->nullable(false);
            $table->float(
                column: 'max_iterations_duration',
                precision: 20,
            )->nullable(false);

            // Meta
            $table->timestamps();

            // Indexes
            $table->unique([
                'name',
                'php_version',
                'laravel_version',
                'mode',
                'max_duration',
                'max_iterations',
                'class',
                'method',
                'phase',
            ], 'kata_reports_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kata_reports');
    }
};
