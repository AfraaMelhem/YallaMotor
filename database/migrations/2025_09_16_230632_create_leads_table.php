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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->string('source')->default('api'); // api, website, phone, etc.
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Lead scoring fields
            $table->integer('score')->nullable();
            $table->enum('status', ['new', 'qualified', 'contacted', 'converted', 'lost'])->default('new');
            $table->json('scoring_data')->nullable(); // Store scoring algorithm results
            $table->timestamp('scored_at')->nullable();

            // Tracking fields
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['listing_id', 'status']);
            $table->index(['email', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['score', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
