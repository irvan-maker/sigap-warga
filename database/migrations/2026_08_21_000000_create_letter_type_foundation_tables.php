<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_TYPES = [
        ['code' => 'GENERAL_INTRODUCTION', 'name' => 'Surat Pengantar Lingkungan RT'],
        ['code' => 'RW_INTRODUCTION', 'name' => 'Surat Pengantar Lingkungan RW'],
        ['code' => 'DOMICILE_CERTIFICATE', 'name' => 'Surat Keterangan Domisili'],
        ['code' => 'LOW_INCOME_CERTIFICATE', 'name' => 'Surat Keterangan Tidak Mampu'],
        ['code' => 'KTP_INTRODUCTION', 'name' => 'Surat Pengantar Administrasi KTP'],
        ['code' => 'SKCK_INTRODUCTION', 'name' => 'Surat Pengantar Administrasi SKCK'],
        ['code' => 'BPJS_HEALTH_INTRODUCTION', 'name' => 'Surat Pengantar Administrasi BPJS Kesehatan'],
    ];

    public function up(): void
    {
        Schema::create('letter_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('letter_type_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_type_id')
                ->constrained('letter_types')
                ->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 20)
                ->default('DRAFT')
                ->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('configuration_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['letter_type_id', 'version']);
            $table->unique(
                ['letter_type_id', 'id'],
                'letter_type_versions_type_id_id_unique',
            );
        });

        Schema::create('letter_workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_type_version_id')
                ->constrained('letter_type_versions')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('action', 40);
            $table->string('actor_scope', 40);
            $table->boolean('is_required')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->unique(['letter_type_version_id', 'sequence']);
            $table->index(['action', 'actor_scope']);
        });

        $timestamp = '2026-08-21 00:00:00';
        $legacyTypes = array_map(
            static fn (array $type): array => [
                'code' => $type['code'],
                'name' => $type['name'],
                'description' => null,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            self::LEGACY_TYPES,
        );

        DB::table('letter_types')->insert($legacyTypes);
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_workflow_steps');
        Schema::dropIfExists('letter_type_versions');
        Schema::dropIfExists('letter_types');
    }
};
