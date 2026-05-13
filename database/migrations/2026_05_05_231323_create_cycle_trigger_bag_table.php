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
        Schema::create('cycle_trigger_bag', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trigger_id')->constrained()->cascadeOnDelete();
            
            $table->timestamps();

            $table->unique(['cycle_id', 'trigger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_trigger_bag');
    }
};
