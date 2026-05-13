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
        Schema::create('trigger_trigger_group', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('trigger_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trigger_id')->constrained()->cascadeOnDelete();
            
            $table->unsignedInteger('sort_order')->default(0);
            
            $table->timestamps();

            $table->unique(['trigger_group_id', 'trigger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hook_hook_group');
    }
};
