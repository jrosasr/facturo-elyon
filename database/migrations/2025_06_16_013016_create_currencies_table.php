<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();

            $table->string('symbol', 4)->comment('Símbolo de la moneda');
            $table->string('code')->unique();
            $table->string('name', 40)->comment('Nombre de la moneda');
            $table->boolean('available')->default(true)->comment('Indica si la moneda está disponible para su uso');
            $table->integer('decimal_places')->default(2)
                    ->comment('Máximo número de decimales a gestionar. El máximo es 10');
            $table->string('decimal_separator')->default(',');
            $table->string('thousands_separator')->default('.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
