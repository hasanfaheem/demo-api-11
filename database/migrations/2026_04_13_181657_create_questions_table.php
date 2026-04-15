<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->enum('response_type', ['scale_1_5', 'yes_no', 'free_text']);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('instrument_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};