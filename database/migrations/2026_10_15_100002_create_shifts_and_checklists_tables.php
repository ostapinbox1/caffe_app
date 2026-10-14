<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Смены и чек-листы: шаблон (конструктор управляющего) → при открытии смены
     * копия шаблона для сотрудника, которую он отмечает.
     */
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['cafe_id', 'started_at']);
        });

        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('deadline_type', 20)->default('shift_end');
            $table->time('due_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('checklist_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_section_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->string('hint')->nullable();
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('checklist_template_position', function (Blueprint $table) {
            $table->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->primary(['checklist_template_id', 'position_id']);
        });

        Schema::create('checklist_template_user', function (Blueprint $table) {
            $table->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['checklist_template_id', 'user_id']);
        });

        Schema::create('checklist_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_shift_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('deadline_type', 20);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();
            $table->timestamps();

            $table->index(['cafe_id', 'created_at']);
        });

        Schema::create('checklist_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_run_id')->constrained()->cascadeOnDelete();
            $table->string('section');
            $table->string('text');
            $table->string('hint')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamp('checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_run_items');
        Schema::dropIfExists('checklist_runs');
        Schema::dropIfExists('checklist_template_user');
        Schema::dropIfExists('checklist_template_position');
        Schema::dropIfExists('checklist_template_items');
        Schema::dropIfExists('checklist_template_sections');
        Schema::dropIfExists('checklist_templates');
        Schema::dropIfExists('work_shifts');
    }
};
