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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');

            // id del cliente
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // fecha de la factura
            $table->date('date');

            // total de la factura
            $table->integer('total')->default(0);

            // estado de la factura
            $table->enum('status', ['paid', 'unpaid', 'canceled'])->default('unpaid');

            // detalles de la factura
            $table->text('details')->nullable();

            // llave unica usando en team_id y el nombre del producto
            $table->unique(['team_id', 'id'], 'user_invoice_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
