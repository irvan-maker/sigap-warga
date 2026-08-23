<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('village_letters', function (Blueprint $table): void {
            $table->string('letter_type', 40)->nullable()->change();
            $table->foreignId('submitted_by')->nullable()->change();
            $table->string('required_approval_level', 20)->nullable()->default('KELURAHAN')->change();
        });

        Schema::create('letter_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_letter_id')
                ->unique()
                ->constrained('village_letters')
                ->restrictOnDelete();
            $table->string('applicant_phone_hash', 64)->index();
            $table->string('letter_type_code', 80);
            $table->string('letter_type_name');
            $table->text('letter_type_description')->nullable();
            $table->unsignedInteger('version_number');
            $table->json('configuration_snapshot');
            $table->timestamp('submitted_at');
            $table->timestamp('sealed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('letter_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_submission_id')
                ->constrained('letter_submissions')
                ->cascadeOnDelete();
            $table->foreignId('letter_field_definition_id')
                ->constrained('letter_field_definitions')
                ->restrictOnDelete();
            $table->string('field_key', 80);
            $table->string('field_label');
            $table->string('field_type', 30);
            $table->unsignedSmallInteger('sequence');
            $table->json('submitted_value')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['letter_submission_id', 'letter_field_definition_id'], 'letter_field_values_definition_unique');
            $table->unique(['letter_submission_id', 'field_key'], 'letter_field_values_key_unique');
        });

        Schema::create('letter_requirement_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_submission_id')
                ->constrained('letter_submissions')
                ->cascadeOnDelete();
            $table->foreignId('letter_requirement_id')
                ->constrained('letter_requirements')
                ->restrictOnDelete();
            $table->string('requirement_key', 80);
            $table->string('requirement_label');
            $table->text('requirement_description')->nullable();
            $table->string('evidence_type', 30);
            $table->boolean('is_required');
            $table->unsignedSmallInteger('sequence');
            $table->string('status', 30)->index();
            $table->json('configuration_snapshot')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['letter_submission_id', 'letter_requirement_id'], 'letter_requirement_submissions_definition_unique');
            $table->unique(['letter_submission_id', 'requirement_key'], 'letter_requirement_submissions_key_unique');
        });

        Schema::create('letter_requirement_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_requirement_submission_id')
                ->unique('letter_requirement_evidences_submission_unique')
                ->constrained('letter_requirement_submissions')
                ->cascadeOnDelete();
            $table->string('disk', 40);
            $table->string('path');
            $table->string('stored_name');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        if (DB::table('village_letters')->whereNull('letter_type')->exists()
            || DB::table('village_letters')->whereNull('submitted_by')->exists()
            || DB::table('village_letters')->whereNull('required_approval_level')->exists()) {
            throw new LogicException('Phase 3 nullable compatibility columns cannot be reverted while generic records exist.');
        }

        Schema::dropIfExists('letter_requirement_evidences');
        Schema::dropIfExists('letter_requirement_submissions');
        Schema::dropIfExists('letter_field_values');
        Schema::dropIfExists('letter_submissions');

        Schema::table('village_letters', function (Blueprint $table): void {
            $table->string('letter_type', 40)->nullable(false)->change();
            $table->foreignId('submitted_by')->nullable(false)->change();
            $table->string('required_approval_level', 20)->nullable(false)->default('KELURAHAN')->change();
        });
    }
};
