<?php

namespace Tests\Feature;

use App\Enums\LetterApprovalLevel;
use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Enums\LetterTypeVersionStatus;
use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\LetterWorkflowStep;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\VillageLetter;
use App\Services\LegacyLetterTypeAdapter;
use App\Services\VillageLetterWorkflow;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LetterFoundationTest extends TestCase
{
    use RefreshDatabase;

    private const HISTORICAL_LETTER_TYPES = [
        'GENERAL_INTRODUCTION' => [
            'name' => 'Surat Pengantar Lingkungan RT',
            'number_code' => 'SP-RT',
            'approval_level' => 'RT',
        ],
        'RW_INTRODUCTION' => [
            'name' => 'Surat Pengantar Lingkungan RW',
            'number_code' => 'SP-RW',
            'approval_level' => 'RW',
        ],
        'DOMICILE_CERTIFICATE' => [
            'name' => 'Surat Keterangan Domisili',
            'number_code' => 'SK-DOM',
            'approval_level' => 'KELURAHAN',
        ],
        'LOW_INCOME_CERTIFICATE' => [
            'name' => 'Surat Keterangan Tidak Mampu',
            'number_code' => 'SK-TM',
            'approval_level' => 'KELURAHAN',
        ],
        'KTP_INTRODUCTION' => [
            'name' => 'Surat Pengantar Administrasi KTP',
            'number_code' => 'SP-KTP',
            'approval_level' => 'KELURAHAN',
        ],
        'SKCK_INTRODUCTION' => [
            'name' => 'Surat Pengantar Administrasi SKCK',
            'number_code' => 'SP-SKCK',
            'approval_level' => 'KELURAHAN',
        ],
        'BPJS_HEALTH_INTRODUCTION' => [
            'name' => 'Surat Pengantar Administrasi BPJS Kesehatan',
            'number_code' => 'SP-BPJS',
            'approval_level' => 'KELURAHAN',
        ],
    ];

    public function test_legacy_letter_type_contract_remains_unchanged(): void
    {
        $this->assertSame(
            array_keys(self::HISTORICAL_LETTER_TYPES),
            array_map(
                static fn (LetterType $type): string => $type->value,
                LetterType::cases(),
            ),
        );

        foreach (self::HISTORICAL_LETTER_TYPES as $code => $expected) {
            $legacyType = LetterType::from($code);

            $this->assertSame($expected['name'], $legacyType->label());
            $this->assertSame($expected['number_code'], $legacyType->code());
            $this->assertSame(
                $expected['approval_level'],
                $legacyType->requiredApprovalLevel()->value,
            );
        }
    }

    public function test_historical_master_types_and_legacy_adapter_match_literal_contract(): void
    {
        $adapter = app(LegacyLetterTypeAdapter::class);

        foreach (self::HISTORICAL_LETTER_TYPES as $code => $expected) {
            $legacyType = LetterType::from($code);

            $this->assertSame($code, $adapter->code($legacyType));

            $definition = $adapter->definitionFor($legacyType);

            $this->assertNotNull($definition);
            $this->assertSame($code, $definition->code);
            $this->assertSame($expected['name'], $definition->name);
            $this->assertSame($legacyType, $definition->legacyType());
            $this->assertTrue($definition->is_active);
            $this->assertNull($definition->description);
        }

        $this->assertDatabaseCount('letter_types', count(self::HISTORICAL_LETTER_TYPES));
        $this->assertCount(count(self::HISTORICAL_LETTER_TYPES), $adapter->resolveAll());
        $this->assertDatabaseCount('letter_types', count(self::HISTORICAL_LETTER_TYPES));
    }

    public function test_matching_type_version_pair_relations_and_step_ordering_work(): void
    {
        [$rtUser, $rt, $citizen] = $this->legacyContext();
        $definition = app(LegacyLetterTypeAdapter::class)
            ->definitionFor(LetterType::DOMICILE_CERTIFICATE);
        $this->assertNotNull($definition);

        $version = LetterTypeVersion::query()->create([
            'letter_type_id' => $definition->id,
            'version' => 1,
            'status' => LetterTypeVersionStatus::PUBLISHED,
            'published_at' => now(),
            'created_by_user_id' => $rtUser->id,
            'configuration_snapshot' => ['source' => 'foundation-test'],
        ]);
        $laterStep = LetterWorkflowStep::query()->create([
            'letter_type_version_id' => $version->id,
            'sequence' => 20,
            'action' => 'APPROVE',
            'actor_scope' => 'KELURAHAN',
            'is_required' => true,
            'configuration' => ['stage' => 'later'],
        ]);
        $earlierStep = LetterWorkflowStep::query()->create([
            'letter_type_version_id' => $version->id,
            'sequence' => 10,
            'action' => 'VERIFY',
            'actor_scope' => 'RT',
            'is_required' => false,
            'configuration' => ['stage' => 'earlier'],
        ]);
        $letter = $this->createLegacyLetter($rtUser, $rt, $citizen, [
            'letter_type' => LetterType::DOMICILE_CERTIFICATE,
            'letter_type_id' => $definition->id,
            'letter_type_version_id' => $version->id,
        ]);

        $this->assertTrue($letter->letterTypeDefinition->is($definition));
        $this->assertTrue($letter->letterTypeVersion->is($version));
        $this->assertTrue($version->typeDefinition->is($definition));
        $this->assertTrue($version->creator->is($rtUser));
        $this->assertTrue($definition->versions->sole()->is($version));
        $this->assertTrue($definition->letters->sole()->is($letter));
        $this->assertTrue($version->letters->sole()->is($letter));
        $this->assertTrue($earlierStep->typeVersion->is($version));
        $this->assertTrue($laterStep->typeVersion->is($version));
        $this->assertSame(
            [$earlierStep->id, $laterStep->id],
            $version->workflowSteps->pluck('id')->all(),
        );
        $this->assertSame(1, $version->version);
        $this->assertSame(10, $earlierStep->sequence);
        $this->assertFalse($earlierStep->is_required);
        $this->assertSame(LetterTypeVersionStatus::PUBLISHED, $version->status);
        $this->assertSame(['source' => 'foundation-test'], $version->configuration_snapshot);
        $this->assertSame(['stage' => 'earlier'], $earlierStep->configuration);
    }

    public function test_legacy_village_letter_without_foundation_foreign_keys_remains_valid(): void
    {
        [$rtUser, $rt, $citizen] = $this->legacyContext();
        $letter = $this->createLegacyLetter($rtUser, $rt, $citizen, [
            'required_approval_level' => LetterApprovalLevel::RT,
        ]);

        $this->assertNull($letter->letter_type_id);
        $this->assertNull($letter->letter_type_version_id);
        $this->assertNull($letter->letterTypeDefinition);
        $this->assertNull($letter->letterTypeVersion);
        $this->assertSame(LetterType::GENERAL_INTRODUCTION, $letter->letter_type);

        app(VillageLetterWorkflow::class)->transition(
            $letter,
            LetterStatus::SUBMITTED,
            $rtUser,
        );

        $letter->refresh();
        $this->assertSame(LetterStatus::APPROVED, $letter->status);
        $this->assertNull($letter->letter_type_id);
        $this->assertNull($letter->letter_type_version_id);
    }

    public function test_link_migration_backfills_pre_existing_known_and_unknown_legacy_rows(): void
    {
        [$rtUser, $rt, $citizen] = $this->legacyContext();
        $migration = require database_path(
            'migrations/2026_08_21_000100_link_village_letters_to_type_foundation.php',
        );
        $migration->down();

        $timestamp = now();
        DB::table('village_letters')->insert([
            [
                'public_tracking_code' => 'SRT-BACKFILL-A',
                'letter_type' => 'GENERAL_INTRODUCTION',
                'required_approval_level' => 'RT',
                'citizen_id' => $citizen->id,
                'rt_id' => $rt->id,
                'submitted_by' => $rtUser->id,
                'purpose' => 'Known legacy backfill fixture',
                'status' => 'DRAFT',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'public_tracking_code' => 'SRT-BACKFILL-B',
                'letter_type' => 'UNKNOWN_LEGACY_TYPE',
                'required_approval_level' => 'KELURAHAN',
                'citizen_id' => $citizen->id,
                'rt_id' => $rt->id,
                'submitted_by' => $rtUser->id,
                'purpose' => 'Unknown legacy backfill fixture',
                'status' => 'DRAFT',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);

        $migration->up();

        $masterTypeId = DB::table('letter_types')
            ->where('code', 'GENERAL_INTRODUCTION')
            ->value('id');
        $this->assertNotNull($masterTypeId);
        $known = DB::table('village_letters')
            ->where('public_tracking_code', 'SRT-BACKFILL-A')
            ->sole();
        $unknown = DB::table('village_letters')
            ->where('public_tracking_code', 'SRT-BACKFILL-B')
            ->sole();

        $this->assertSame('GENERAL_INTRODUCTION', $known->letter_type);
        $this->assertSame((int) $masterTypeId, (int) $known->letter_type_id);
        $this->assertNull($known->letter_type_version_id);
        $this->assertSame('UNKNOWN_LEGACY_TYPE', $unknown->letter_type);
        $this->assertNull($unknown->letter_type_id);
        $this->assertNull($unknown->letter_type_version_id);
    }

    public function test_mismatched_type_and_version_pair_is_rejected_by_the_database(): void
    {
        [$rtUser, $rt, $citizen] = $this->legacyContext();
        $typeA = app(LegacyLetterTypeAdapter::class)
            ->definitionFor(LetterType::GENERAL_INTRODUCTION);
        $typeB = app(LegacyLetterTypeAdapter::class)
            ->definitionFor(LetterType::RW_INTRODUCTION);
        $this->assertNotNull($typeA);
        $this->assertNotNull($typeB);
        $versionB = LetterTypeVersion::query()->create([
            'letter_type_id' => $typeB->id,
            'version' => 1,
        ]);

        $this->expectException(QueryException::class);

        $this->createLegacyLetter($rtUser, $rt, $citizen, [
            'letter_type_id' => $typeA->id,
            'letter_type_version_id' => $versionB->id,
        ]);
    }

    public function test_duplicate_master_type_code_is_rejected_by_the_database(): void
    {
        LetterTypeDefinition::query()->create([
            'code' => 'FOUNDATION_TEST',
            'name' => 'Foundation Test',
            'is_active' => true,
        ]);

        $this->expectException(QueryException::class);

        LetterTypeDefinition::query()->create([
            'code' => 'FOUNDATION_TEST',
            'name' => 'Duplicate Foundation Test',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_version_per_master_type_is_rejected_by_the_database(): void
    {
        $definition = app(LegacyLetterTypeAdapter::class)
            ->definitionFor(LetterType::LOW_INCOME_CERTIFICATE);
        $this->assertNotNull($definition);
        LetterTypeVersion::query()->create([
            'letter_type_id' => $definition->id,
            'version' => 1,
        ]);

        $this->expectException(QueryException::class);

        LetterTypeVersion::query()->create([
            'letter_type_id' => $definition->id,
            'version' => 1,
        ]);
    }

    public function test_same_version_number_is_allowed_for_different_master_types(): void
    {
        $firstDefinition = app(LegacyLetterTypeAdapter::class)
            ->definitionFor(LetterType::GENERAL_INTRODUCTION);
        $secondDefinition = app(LegacyLetterTypeAdapter::class)
            ->definitionFor(LetterType::RW_INTRODUCTION);
        $this->assertNotNull($firstDefinition);
        $this->assertNotNull($secondDefinition);

        $firstVersion = LetterTypeVersion::query()->create([
            'letter_type_id' => $firstDefinition->id,
            'version' => 1,
        ]);
        $secondVersion = LetterTypeVersion::query()->create([
            'letter_type_id' => $secondDefinition->id,
            'version' => 1,
        ]);

        $this->assertNotSame($firstVersion->id, $secondVersion->id);
        $this->assertSame(1, $firstVersion->version);
        $this->assertSame(1, $secondVersion->version);
    }

    public function test_duplicate_workflow_sequence_in_one_version_is_rejected(): void
    {
        $definition = app(LegacyLetterTypeAdapter::class)
            ->definitionFor(LetterType::DOMICILE_CERTIFICATE);
        $this->assertNotNull($definition);
        $version = LetterTypeVersion::query()->create([
            'letter_type_id' => $definition->id,
            'version' => 1,
        ]);
        LetterWorkflowStep::query()->create([
            'letter_type_version_id' => $version->id,
            'sequence' => 10,
            'action' => 'VERIFY',
            'actor_scope' => 'RT',
        ]);

        $this->expectException(QueryException::class);

        LetterWorkflowStep::query()->create([
            'letter_type_version_id' => $version->id,
            'sequence' => 10,
            'action' => 'APPROVE',
            'actor_scope' => 'KELURAHAN',
        ]);
    }

    public function test_same_workflow_sequence_is_allowed_for_different_versions(): void
    {
        $definition = app(LegacyLetterTypeAdapter::class)
            ->definitionFor(LetterType::DOMICILE_CERTIFICATE);
        $this->assertNotNull($definition);
        $firstVersion = LetterTypeVersion::query()->create([
            'letter_type_id' => $definition->id,
            'version' => 1,
        ]);
        $secondVersion = LetterTypeVersion::query()->create([
            'letter_type_id' => $definition->id,
            'version' => 2,
        ]);

        $firstStep = LetterWorkflowStep::query()->create([
            'letter_type_version_id' => $firstVersion->id,
            'sequence' => 10,
            'action' => 'VERIFY',
            'actor_scope' => 'RT',
        ]);
        $secondStep = LetterWorkflowStep::query()->create([
            'letter_type_version_id' => $secondVersion->id,
            'sequence' => 10,
            'action' => 'VERIFY',
            'actor_scope' => 'RT',
        ]);

        $this->assertNotSame($firstStep->id, $secondStep->id);
        $this->assertSame(10, $firstStep->sequence);
        $this->assertSame(10, $secondStep->sequence);
    }

    /** @return array{User, Rt, Citizen} */
    private function legacyContext(): array
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $rt = Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '001',
            'name' => 'RT 001',
        ]);
        $rtUser = User::factory()->create([
            'role' => UserRole::RT,
            'position' => null,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);
        $citizen = Citizen::factory()->for($rt)->create();

        return [$rtUser, $rt, $citizen];
    }

    /** @param array<string, mixed> $overrides */
    private function createLegacyLetter(
        User $submitter,
        Rt $rt,
        Citizen $citizen,
        array $overrides = [],
    ): VillageLetter {
        return VillageLetter::query()->create([
            'letter_type' => LetterType::GENERAL_INTRODUCTION,
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
            'submitted_by' => $submitter->id,
            'purpose' => 'Keperluan foundation test',
            'status' => LetterStatus::DRAFT,
            ...$overrides,
        ]);
    }
}
