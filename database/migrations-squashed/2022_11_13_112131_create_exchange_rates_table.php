<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Currency::class, 'base_currency_id')->constrained('currencies');
            $table->foreignIdFor(Currency::class, 'target_currency_id')->constrained('currencies');
            $table->string('target_currency_code');
            $table->date('date');
            $table->decimal('rate', 10, 3);
            $table->timestamps();

            $table->unique(['base_currency_id', 'target_currency_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('exchange_rates');
    }
};
