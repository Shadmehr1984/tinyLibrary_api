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
        Schema::create('borrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->references('id')->on('members');
            $table->foreignId('book_id')->references('id')->on('books');
            $table->date('borrowed_at');
            $table->unique(['member_id', 'book_id', 'borrowed_at']);
            $table->date('due_date')->nullable(true)->default(null);
            $table->date('returned_at')->nullable(true)->default(null);
            $table->enum('status', ['borrowed', 'returned', 'overdue']);
            $table->integer('penalty_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrows');
    }
};
