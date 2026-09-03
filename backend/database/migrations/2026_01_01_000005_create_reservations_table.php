<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            $table->dateTime('reservation_time');
            $table->unsignedTinyInteger('guest_count');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();

            // Sprečavanje duplih rezervacija istog stola u istom terminu
            $table->unique(['table_id', 'reservation_time'], 'uq_table_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
