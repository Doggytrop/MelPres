<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('actor_user_id', 'saas_logs_actor_idx');
            $table->index('action', 'saas_logs_action_idx');
            $table->index(['subject_type', 'subject_id'], 'saas_logs_subject_idx');
            $table->index('created_at', 'saas_logs_created_at_idx');
            $table->foreign('actor_user_id', 'saas_logs_actor_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_activity_logs');
    }
};
