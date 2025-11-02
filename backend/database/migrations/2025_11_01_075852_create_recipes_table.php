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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->json('ingredients'); // Store ingredients as JSON array
            $table->json('steps'); // Store cooking steps as JSON array
            $table->string('image')->nullable(); // Path to recipe image
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cuisine_type_id')->constrained()->onDelete('restrict');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('cuisine_type_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
