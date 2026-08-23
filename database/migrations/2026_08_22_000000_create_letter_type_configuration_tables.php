<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_type_version_id')
                ->constrained('letter_type_versions')
                ->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('evidence_type', 30)->nullable()->index();
            $table->unsignedSmallInteger('sequence');
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->unique(['letter_type_version_id', 'key']);
            $table->unique(['letter_type_version_id', 'sequence']);
        });

        Schema::create('letter_field_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_type_version_id')
                ->constrained('letter_type_versions')
                ->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('label');
            $table->string('field_type', 30)->index();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sequence');
            $table->string('data_source', 30)->nullable()->index();
            $table->json('validation')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->unique(['letter_type_version_id', 'key']);
            $table->unique(['letter_type_version_id', 'sequence']);
        });

        Schema::table('letter_workflow_steps', function (Blueprint $table): void {
            $table->string('actor_role', 30)->nullable();
            $table->string('village_position', 30)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('letter_workflow_steps', function (Blueprint $table): void {
            $table->dropIndex(['village_position']);
            $table->dropColumn(['village_position', 'actor_role']);
        });

        Schema::dropIfExists('letter_field_definitions');
        Schema::dropIfExists('letter_requirements');
    }
};
