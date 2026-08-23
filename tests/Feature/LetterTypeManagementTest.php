<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Enums\LetterTypeVersionStatus;
use App\Enums\LetterWorkflowAction;
use App\Enums\LetterWorkflowActorScope;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Citizen;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\VillageLetter;
use App\Services\LetterTypeConfigurationService;
use App\Services\LetterTypeDefinitionService;
use App\Services\LetterTypeVersionService;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LetterTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_master_access_uses_existing_narrow_village_configuration_matrix(): void
    {
        $this->get(route('kelurahan.letter-types.index'))->assertRedirect(route('login'));

        $systemAdmin = $this->villageOfficer(VillagePosition::SYSTEM_ADMIN);
        $secretary = $this->villageOfficer(VillagePosition::VILLAGE_SECRETARY);
        $head = $this->villageOfficer(VillagePosition::VILLAGE_HEAD);

        foreach ([$systemAdmin, $secretary, $head] as $viewer) {
            $this->actingAs($viewer)->get(route('kelurahan.letter-types.index'))->assertOk();
        }

        foreach ([$systemAdmin, $secretary] as $index => $manager) {
            $this->actingAs($manager)->post(route('kelurahan.letter-types.store'), [
                'code' => "AUTHORIZED_{$index}",
                'name' => "Authorized {$index}",
                'is_active' => '1',
            ])->assertRedirect();
        }

        $this->actingAs($head)->post(route('kelurahan.letter-types.store'), [
            'code' => 'HEAD_DENIED',
            'name' => 'Head Denied',
            'is_active' => '1',
        ])->assertForbidden();

        $systemAdminDraft = LetterTypeDefinition::query()->where('code', 'AUTHORIZED_0')->sole()->draftVersion;
        $this->createMinimalWorkflow($systemAdminDraft);
        $this->actingAs($systemAdmin)
            ->post(route('kelurahan.letter-type-versions.publish', $systemAdminDraft))
            ->assertRedirect(route('kelurahan.letter-type-versions.show', $systemAdminDraft));

        $existingDraft = LetterTypeDefinition::query()->where('code', 'AUTHORIZED_1')->sole()->draftVersion;
        foreach ([LetterWorkflowAction::VERIFY, LetterWorkflowAction::APPROVE, LetterWorkflowAction::SIGN] as $businessAction) {
            $this->actingAs($systemAdmin)
                ->post(route('kelurahan.letter-type-versions.workflow.store', $existingDraft), [
                    'sequence' => 10,
                    'action' => $businessAction->value,
                    'actor_scope' => LetterWorkflowActorScope::KELURAHAN->value,
                    'village_position' => VillagePosition::SYSTEM_ADMIN->value,
                    'is_required' => '1',
                ])
                ->assertSessionHasErrors('village_position');
        }
        $this->assertSame(0, $existingDraft->workflowSteps()->count());
        $this->actingAs($head)
            ->get(route('kelurahan.letter-type-versions.show', $existingDraft))
            ->assertOk()
            ->assertSee('akses review saja');
        $this->actingAs($head)
            ->post(route('kelurahan.letter-type-versions.publish', $existingDraft))
            ->assertForbidden();

        [$rw, $rt] = $this->region();
        foreach ([
            User::factory()->create(['role' => UserRole::RW, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => null]),
            User::factory()->create(['role' => UserRole::RT, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => $rt->id]),
            $this->villageOfficer(VillagePosition::VILLAGE_SECRETARY, false),
        ] as $denied) {
            $this->actingAs($denied)->get(route('kelurahan.letter-types.index'))->assertForbidden();
        }
    }

    public function test_master_create_unique_code_deactivate_and_legacy_contract_work(): void
    {
        $secretary = $this->villageOfficer(VillagePosition::VILLAGE_SECRETARY);
        $payload = [
            'code' => 'CUSTOM_CERTIFICATE',
            'name' => 'Surat Keterangan Kustom',
            'description' => 'Definition khusus pengujian.',
            'is_active' => '1',
        ];

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-types.store'), $payload)
            ->assertRedirect();

        $letterType = LetterTypeDefinition::query()->where('code', 'CUSTOM_CERTIFICATE')->sole();
        $draft = $letterType->versions()->sole();
        $this->assertSame(1, $draft->version);
        $this->assertSame(LetterTypeVersionStatus::DRAFT, $draft->status);
        $this->assertSame($secretary->id, $draft->created_by_user_id);

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-types.store'), $payload)
            ->assertSessionHasErrors('code');

        $this->actingAs($secretary)
            ->put(route('kelurahan.letter-types.update', $letterType), [
                ...$payload,
                'is_active' => '0',
            ])
            ->assertRedirect(route('kelurahan.letter-types.edit', $letterType));
        $this->assertFalse($letterType->fresh()->is_active);
        $this->assertDatabaseHas('letter_types', ['id' => $letterType->id, 'is_active' => false]);

        foreach (LetterType::cases() as $legacyType) {
            $this->assertDatabaseHas('letter_types', ['code' => $legacyType->value]);
        }
    }

    public function test_draft_publish_increment_clone_and_immutability_lifecycle(): void
    {
        $secretary = $this->villageOfficer(VillagePosition::VILLAGE_SECRETARY);
        [$letterType, $draft] = app(LetterTypeDefinitionService::class)->createWithDraft([
            'code' => 'VERSIONED_CERTIFICATE',
            'name' => 'Surat Versioned',
            'description' => null,
            'is_active' => true,
        ], $secretary);

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.publish', $draft))
            ->assertSessionHasErrors('configuration');
        $this->assertTrue($draft->fresh()->isDraft());

        $this->createMinimalWorkflow($draft, ['channel' => 'administrative']);

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.publish', $draft))
            ->assertRedirect(route('kelurahan.letter-type-versions.show', $draft));

        $published = $draft->fresh();
        $this->assertTrue($published->isPublished());
        $this->assertNotNull($published->published_at);
        $this->assertSame(1, $published->configuration_snapshot['schema_version']);
        $this->assertCount(4, $published->configuration_snapshot['workflow']);
        $this->actingAs($secretary)
            ->get(route('kelurahan.letter-type-versions.show', $published))
            ->assertOk()
            ->assertSee('Published dan immutable');

        $this->actingAs($secretary)
            ->delete(route('kelurahan.letter-type-versions.destroy', $published))
            ->assertForbidden();
        $this->assertDatabaseHas('letter_type_versions', ['id' => $published->id]);

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-types.versions.store', $letterType))
            ->assertRedirect();
        $newDraft = LetterTypeVersion::query()
            ->where('letter_type_id', $letterType->id)
            ->where('status', LetterTypeVersionStatus::DRAFT->value)
            ->sole();
        $this->assertSame(2, $newDraft->version);
        $this->assertCount(4, $newDraft->workflowSteps);
        $this->assertSame(1, $published->fresh()->version);
        $this->assertSame(['channel' => 'administrative'], $newDraft->workflowSteps->last()->configuration);

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-types.versions.store', $letterType))
            ->assertSessionHasErrors('version');

        try {
            app(LetterTypeVersionService::class)->deleteDraft($published);
            $this->fail('Published version unexpectedly deleted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('version', $exception->errors());
        }
    }

    public function test_published_referenced_and_legacy_type_codes_are_stable(): void
    {
        $secretary = $this->villageOfficer(VillagePosition::VILLAGE_SECRETARY);
        [$letterType, $draft] = app(LetterTypeDefinitionService::class)->createWithDraft([
            'code' => 'STABLE_CODE',
            'name' => 'Stable Code',
            'description' => null,
            'is_active' => true,
        ], $secretary);
        $this->createMinimalWorkflow($draft);
        app(LetterTypeVersionService::class)->publish($draft);

        $this->actingAs($secretary)->put(route('kelurahan.letter-types.update', $letterType), [
            'code' => 'CHANGED_CODE',
            'name' => $letterType->name,
            'description' => null,
            'is_active' => '1',
        ])->assertSessionHasErrors('code');

        $legacy = LetterTypeDefinition::query()->where('code', LetterType::GENERAL_INTRODUCTION->value)->sole();
        $this->actingAs($secretary)->put(route('kelurahan.letter-types.update', $legacy), [
            'code' => 'CHANGED_LEGACY',
            'name' => $legacy->name,
            'description' => null,
            'is_active' => '1',
        ])->assertSessionHasErrors('code');

        [$referencedType] = app(LetterTypeDefinitionService::class)->createWithDraft([
            'code' => 'REFERENCED_CODE',
            'name' => 'Referenced Code',
            'description' => null,
            'is_active' => true,
        ], $secretary);
        [$rw, $rt] = $this->region();
        $rtUser = User::factory()->create([
            'role' => UserRole::RT,
            'position' => null,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);
        $citizen = Citizen::factory()->for($rt)->create();
        VillageLetter::query()->create([
            'letter_type' => LetterType::GENERAL_INTRODUCTION,
            'letter_type_id' => $referencedType->id,
            'letter_type_version_id' => null,
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
            'submitted_by' => $rtUser->id,
            'purpose' => 'Fixture application reference',
            'status' => LetterStatus::DRAFT,
        ]);
        $this->actingAs($secretary)->put(route('kelurahan.letter-types.update', $referencedType), [
            'code' => 'CHANGED_REFERENCE',
            'name' => $referencedType->name,
            'description' => null,
            'is_active' => '1',
        ])->assertSessionHasErrors('code');
    }

    public function test_direct_version_lifecycle_mutation_is_rejected_but_service_publish_succeeds(): void
    {
        $secretary = $this->villageOfficer(VillagePosition::VILLAGE_SECRETARY);
        $systemAdmin = $this->villageOfficer(VillagePosition::SYSTEM_ADMIN);
        [$letterType, $draft] = app(LetterTypeDefinitionService::class)->createWithDraft([
            'code' => 'VERSION_MODEL_GUARD',
            'name' => 'Version Model Guard',
            'description' => null,
            'is_active' => true,
        ], $secretary);
        [$otherType] = app(LetterTypeDefinitionService::class)->createWithDraft([
            'code' => 'VERSION_MODEL_GUARD_OTHER',
            'name' => 'Version Model Guard Other',
            'description' => null,
            'is_active' => true,
        ], $secretary);

        $mutations = [
            'status' => ['status' => LetterTypeVersionStatus::PUBLISHED],
            'published_at' => ['published_at' => now()],
            'configuration_snapshot' => ['configuration_snapshot' => ['schema_version' => 999]],
            'version' => ['version' => 99],
            'letter_type_id' => ['letter_type_id' => $otherType->id],
        ];
        foreach ($mutations as $field => $attributes) {
            $this->assertValidationRejected(
                fn () => LetterTypeVersion::query()->findOrFail($draft->id)->update($attributes),
                'version',
                "Direct {$field} mutation unexpectedly changed the version lifecycle.",
            );
        }

        $this->assertDatabaseHas('letter_type_versions', [
            'id' => $draft->id,
            'letter_type_id' => $letterType->id,
            'version' => 1,
            'status' => LetterTypeVersionStatus::DRAFT->value,
            'published_at' => null,
            'configuration_snapshot' => null,
            'created_by_user_id' => $secretary->id,
        ]);

        $this->createMinimalWorkflow($draft);
        $published = app(LetterTypeVersionService::class)->publish($draft);
        $this->assertTrue($published->isPublished());
        $this->assertNotNull($published->published_at);
        $this->assertSame(1, $published->configuration_snapshot['schema_version']);

        $this->assertValidationRejected(
            fn () => $published->fresh()->update(['created_by_user_id' => $systemAdmin->id]),
            'version',
            'A published version accepted an arbitrary update.',
        );
        $this->assertValidationRejected(
            fn () => $published->fresh()->publishValidatedConfiguration(fn (): array => ['schema_version' => 999]),
            'version',
            'A published version accepted a second publish transition.',
        );
        $this->assertValidationRejected(
            fn () => $published->fresh()->delete(),
            'version',
            'A published version was directly deleted.',
        );
        $this->assertDatabaseHas('letter_type_versions', [
            'id' => $published->id,
            'created_by_user_id' => $secretary->id,
            'status' => LetterTypeVersionStatus::PUBLISHED->value,
        ]);
    }

    public function test_direct_sequential_duplicate_draft_is_rejected_without_blocking_history_or_service_flow(): void
    {
        $secretary = $this->villageOfficer(VillagePosition::VILLAGE_SECRETARY);
        [$letterType, $firstDraft] = app(LetterTypeDefinitionService::class)->createWithDraft([
            'code' => 'DIRECT_DRAFT_GUARD',
            'name' => 'Direct Draft Guard',
            'description' => null,
            'is_active' => true,
        ], $secretary);
        $this->createMinimalWorkflow($firstDraft);
        app(LetterTypeVersionService::class)->publish($firstDraft);

        $secondDraft = app(LetterTypeVersionService::class)->createDraft($letterType, $secretary);
        $this->assertSame(2, $secondDraft->version);
        $this->assertTrue($secondDraft->isDraft());

        $this->assertValidationRejected(
            fn () => $letterType->versions()->create([
                'version' => 3,
                'status' => LetterTypeVersionStatus::DRAFT,
                'created_by_user_id' => $secretary->id,
            ]),
            'version',
            'Direct Eloquent creation unexpectedly created a second draft.',
        );

        $historical = $letterType->versions()->create([
            'version' => 3,
            'status' => LetterTypeVersionStatus::PUBLISHED,
            'published_at' => now(),
            'created_by_user_id' => $secretary->id,
            'configuration_snapshot' => ['schema_version' => 1],
        ]);

        $this->assertTrue($historical->isPublished());
        $this->assertSame(1, $letterType->versions()->where('status', LetterTypeVersionStatus::DRAFT->value)->count());
        $this->assertSame(2, $letterType->versions()->where('status', LetterTypeVersionStatus::PUBLISHED->value)->count());
    }

    public function test_stale_draft_requests_preserve_one_draft_and_deterministic_increment(): void
    {
        $secretary = $this->villageOfficer(VillagePosition::VILLAGE_SECRETARY);
        $systemAdmin = $this->villageOfficer(VillagePosition::SYSTEM_ADMIN);
        [$letterType, $firstDraft] = app(LetterTypeDefinitionService::class)->createWithDraft([
            'code' => 'DRAFT_RACE_GUARD',
            'name' => 'Draft Race Guard',
            'description' => null,
            'is_active' => true,
        ], $secretary);
        $this->createMinimalWorkflow($firstDraft);
        app(LetterTypeVersionService::class)->publish($firstDraft);

        $staleForSecretary = LetterTypeDefinition::query()->findOrFail($letterType->id);
        $staleForAdmin = LetterTypeDefinition::query()->findOrFail($letterType->id);
        $secondDraft = app(LetterTypeVersionService::class)->createDraft($staleForSecretary, $secretary);

        try {
            app(LetterTypeVersionService::class)->createDraft($staleForAdmin, $systemAdmin);
            $this->fail('A second active draft was unexpectedly created.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('version', $exception->errors());
        }

        $this->assertSame(2, $secondDraft->version);
        $this->assertSame($secretary->id, $secondDraft->created_by_user_id);
        $this->assertSame(1, $letterType->versions()->where('status', LetterTypeVersionStatus::DRAFT->value)->count());
    }

    private function createMinimalWorkflow(LetterTypeVersion $draft, ?array $issueConfiguration = null): void
    {
        $configuration = app(LetterTypeConfigurationService::class);
        foreach ([
            [10, LetterWorkflowAction::SUBMIT, LetterWorkflowActorScope::CITIZEN, null, null],
            [20, LetterWorkflowAction::APPROVE, LetterWorkflowActorScope::KELURAHAN, VillagePosition::VILLAGE_SECRETARY, null],
            [30, LetterWorkflowAction::SIGN, LetterWorkflowActorScope::KELURAHAN, VillagePosition::VILLAGE_HEAD, null],
            [40, LetterWorkflowAction::ISSUE, LetterWorkflowActorScope::KELURAHAN, VillagePosition::VILLAGE_SECRETARY, $issueConfiguration],
        ] as [$sequence, $action, $scope, $position, $stepConfiguration]) {
            $configuration->createWorkflowStep($draft, [
                'sequence' => $sequence,
                'action' => $action->value,
                'actor_scope' => $scope->value,
                'village_position' => $position?->value,
                'is_required' => true,
                'configuration' => $stepConfiguration,
            ]);
        }
    }

    private function villageOfficer(VillagePosition $position, bool $active = true): User
    {
        return User::factory()->create([
            'role' => $position === VillagePosition::SYSTEM_ADMIN ? UserRole::ADMIN : UserRole::KELURAHAN,
            'position' => $position,
            'is_active' => $active,
            'rw_id' => null,
            'rt_id' => null,
        ]);
    }

    /** @return array{Rw, Rt} */
    private function region(): array
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $rt = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);

        return [$rw, $rt];
    }

    private function assertValidationRejected(
        Closure $operation,
        string $errorKey,
        string $failureMessage,
    ): void {
        try {
            $operation();
            $this->fail($failureMessage);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($errorKey, $exception->errors());
        }
    }
}
