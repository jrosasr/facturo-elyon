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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('cost')->default(0);
            $table->integer('price')->default(0);
            $table->integer('stock')->default(0);
            $table->integer('stock_min')->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('image')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // llave unica usando en user_id y el nombre del producto
            $table->unique(['user_id', 'name'], 'user_product_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
