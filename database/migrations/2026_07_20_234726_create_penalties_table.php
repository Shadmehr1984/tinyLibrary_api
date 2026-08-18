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
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowed_id')->references('id')->on('borrows');
            $table->foreignId('member_id')->references('id')->on('members');
            $table->unique(['member_id', 'borrowed_id']);
            $table->integer('amount');
            $table->date('calculated_at');
            $table->date('paid_at')->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};
