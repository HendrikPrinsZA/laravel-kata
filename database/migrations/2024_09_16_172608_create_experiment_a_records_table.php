<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiment_a_records', function (Blueprint $table) {
            $table->id();
            $table->string('unique_field_1', 255);
            $table->string('unique_field_2', 255);
            $table->string('unique_field_3', 255);
            $table->string('update_field_1', 255);
            $table->string('update_field_2', 255);
            $table->string('update_field_3', 255);
            $table->timestamps();

            $table->unique(['unique_field_1', 'unique_field_2', 'unique_field_3'], 'unique_fields');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_a1_records');
    }
};
