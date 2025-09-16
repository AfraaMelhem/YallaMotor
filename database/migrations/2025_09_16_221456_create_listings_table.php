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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->onDelete('cascade');
            $table->string('make');
            $table->string('model');
            $table->year('year');
            $table->bigInteger('price_cents');
            $table->integer('mileage_km');
            $table->string('country_code', 2);
            $table->string('city');
            $table->enum('status', ['active', 'sold', 'hidden'])->default('active');
            $table->timestamp('listed_at');
            $table->timestamps();

            $table->index(['status', 'country_code']);
            $table->index(['make', 'model']);
            $table->index(['price_cents']);
            $table->index(['listed_at']);
            $table->index(['dealer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
