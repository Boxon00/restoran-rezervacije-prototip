<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ovo je migracija koju standardno obezbeđuje Laravel Sanctum paket (obično
 * kopirana u database/migrations/ komandom `php artisan install:api` ili
 * `vendor:publish --tag=sanctum-migrations` prilikom instalacije paketa).
 *
 * BEZ ove tabele, poziv $user->createToken('api') u AuthController-u
 * (register/login) pokušava da upiše novi token u nepostojeću tabelu, što
 * uzrokuje SQL grešku "Base table or view not found: personal_access_tokens"
 * i vraća HTTP 500 na svaki pokušaj registracije ili prijave — tačno grešku
 * koja se javljala pre dodavanja ove migracije.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
