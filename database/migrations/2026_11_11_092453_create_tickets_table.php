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
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('ticket_number')->unique(); // Contoh: TKT-2026001
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->text('description');
        $table->enum('category', ['Hardware', 'Software', 'Network', 'Account', 'Other']);
        $table->enum('priority', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
        $table->enum('status', ['Open', 'In Progress', 'Resolved', 'Closed'])->default('Open');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
