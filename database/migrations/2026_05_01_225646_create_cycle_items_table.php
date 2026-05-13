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
        Schema::create('cycle_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trigger_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hook_id')->nullable()->constrained()->cascadeOnDelete();

            $table->text('hook_text')->nullable();
            $table->text('idea_text')->nullable();
            $table->text('note')->nullable();

            $table->boolean('is_pinned')->default(false);
            $table->timestamp('pinned_at')->nullable();

            $table->unsignedInteger('position');
            $table->string('board_state')->default('deck');
            
            $table->timestamps();

            $table->unique(['cycle_id', 'position']);
            $table->unique(['cycle_id', 'trigger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_items');
    }
};
