<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Адрес кофейни, должности (к ним привязаны чек-листы), ФИО и аватар сотрудника.
     */
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->string('address')->nullable()->after('name');
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['cafe_id', 'name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('avatar_path')->nullable()->after('middle_name');
            $table->foreignId('position_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        // Должности из старого текстового поля.
        foreach (DB::table('users')->whereNotNull('position')->whereNotNull('cafe_id')->get() as $user) {
            $positionId = DB::table('positions')->where(['cafe_id' => $user->cafe_id, 'name' => $user->position])->value('id')
                ?? DB::table('positions')->insertGetId(['cafe_id' => $user->cafe_id, 'name' => $user->position, 'created_at' => now(), 'updated_at' => now()]);

            DB::table('users')->where('id', $user->id)->update(['position_id' => $positionId, 'first_name' => $user->name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable();
            $table->dropConstrainedForeignId('position_id');
            $table->dropColumn(['last_name', 'first_name', 'middle_name', 'avatar_path']);
        });

        Schema::dropIfExists('positions');

        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
