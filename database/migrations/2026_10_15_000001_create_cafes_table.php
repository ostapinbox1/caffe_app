<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Кофейня — «арендатор»: все данные приложения привязаны к ней,
     * чтобы потом можно было подключать другие кафе.
     */
    public function up(): void
    {
        Schema::create('cafes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cafe_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->string('role', 20)->default('staff')->after('email');
            $table->string('position')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cafe_id');
            $table->dropColumn(['role', 'position', 'is_active']);
        });

        Schema::dropIfExists('cafes');
    }
};
