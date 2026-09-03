<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city')->default('Niš');
            $table->string('cuisine_type')->nullable();
            $table->string('phone')->nullable();
            $table->string('cover_image')->nullable();
            $table->time('opening_time')->default('10:00:00');
            $table->time('closing_time')->default('23:00:00');
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
