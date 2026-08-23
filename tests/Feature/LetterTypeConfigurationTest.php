<?php

namespace Tests\Feature;

use App\Enums\LetterFieldDataSource;
use App\Enums\LetterFieldType;
use App\Enums\LetterRequirementEvidenceType;
use App\Enums\LetterTypeVersionStatus;
use App\Enums\LetterWorkflowAction;
use App\Enums\LetterWorkflowActorScope;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\LetterFieldDefinition;
use App\Models\LetterRequirement;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\User;
use App\Services\LetterTypeConfigurationService;
use App\Services\LetterTypeDefinitionService;
use App\Services\LetterTypeVersionService;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LetterTypeConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_requirements_support_draft_crud_uniqueness_ordering_and_published_immutability(): void
    {
        [$secretary, , $draft] = $this->draft('REQUIREMENT_TEST');

        foreach ([
            ['key' => 'KK', 'label' => 'Kartu Keluarga', 'sequence' => 20],
            ['key' => 'KTP', 'label' => 'Kartu Tanda Penduduk', 'sequence' => 10],
        ] as $requirement) {
            $this->actingAs($secretary)
                ->post(route('kelurahan.letter-type-versions.requirements.store', $draft), [
                    ...$requirement,
                    'description' => null,
                    'is_required' => '1',
                    'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
                    'configuration' => '{"source":"verified-master"}',
                ])
                ->assertRedirect();
        }

        $this->assertSame(['KTP', 'KK'], $draft->requirements()->pluck('key')->all());
        $ktp = LetterRequirement::query()->where('letter_type_version_id', $draft->id)->where('key', 'KTP')->sole();
        $this->assertSame(LetterRequirementEvidenceType::MASTER_DATA, $ktp->evidence_type);
        $this->assertSame(['source' => 'verified-master'], $ktp->configuration);
        $this->actingAs($secretary)
            ->get(route('kelurahan.letter-type-versions.show', $draft))
            ->assertOk()
            ->assertSee('Kartu Tanda Penduduk');

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.requirements.store', $draft), [
                'key' => 'KTP',
                'label' => 'Duplicate KTP',
                'sequence' => 30,
                'is_required' => '1',
                'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
            ])
            ->assertSessionHasErrors('key');

        $this->actingAs($secretary)
            ->put(route('kelurahan.letter-type-versions.requirements.update', [$draft, $ktp]), [
                'key' => 'KTP',
                'label' => 'Identitas KTP',
                'sequence' => 10,
                'is_required' => '1',
                'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
                'configuration' => '{}',
            ])->assertRedirect();
        $this->assertSame('Identitas KTP', $ktp->fresh()->label);

        $kk = LetterRequirement::query()->where('letter_type_version_id', $draft->id)->where('key', 'KK')->sole();
        $this->actingAs($secretary)
            ->delete(route('kelurahan.letter-type-versions.requirements.destroy', [$draft, $kk]))
            ->assertRedirect();
        $this->assertDatabaseMissing('letter_requirements', ['id' => $kk->id]);

        $this->makePublishable($draft);
        app(LetterTypeVersionService::class)->publish($draft);

        $this->actingAs($secretary)
            ->put(route('kelurahan.letter-type-versions.requirements.update', [$draft, $ktp]), [
                'key' => 'KTP',
                'label' => 'Mutasi terlarang',
                'sequence' => 10,
                'is_required' => '1',
                'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
            ])->assertForbidden();
        $this->actingAs($secretary)
            ->delete(route('kelurahan.letter-type-versions.requirements.destroy', [$draft, $ktp]))
            ->assertForbidden();
        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.requirements.store', $draft), [
                'key' => 'PUBLISHED_INSERT',
                'label' => 'Published Insert',
                'sequence' => 20,
                'is_required' => '1',
                'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
            ])->assertForbidden();
        $this->assertSame('Identitas KTP', $ktp->fresh()->label);

        $this->expectException(ValidationException::class);
        app(LetterTypeConfigurationService::class)->updateRequirement($draft->fresh(), $ktp, [
            'label' => 'Service mutation forbidden',
        ]);
    }

    public function test_fields_enforce_allowlist_json_casts_uniqueness_and_publish_validation(): void
    {
        [$secretary, , $draft] = $this->draft('FIELD_TEST');

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.fields.store', $draft), [
                'key' => 'unsafe_field',
                'label' => 'Unsafe Field',
                'field_type' => 'php',
                'sequence' => 10,
                'is_required' => '0',
            ])
            ->assertSessionHasErrors('field_type');

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.fields.store', $draft), [
                'key' => 'tanggal_keperluan',
                'label' => 'Tanggal Keperluan',
                'field_type' => LetterFieldType::DATE->value,
                'sequence' => 10,
                'is_required' => '1',
                'data_source' => LetterFieldDataSource::CITIZEN->value,
                'validation' => '{"after_or_equal":"today"}',
                'configuration' => '{"display":"calendar"}',
            ])->assertRedirect();

        $field = LetterFieldDefinition::query()->where('letter_type_version_id', $draft->id)->sole();
        $this->assertSame(LetterFieldType::DATE, $field->field_type);
        $this->assertSame(LetterFieldDataSource::CITIZEN, $field->data_source);
        $this->assertSame(['after_or_equal' => 'today'], $field->validation);
        $this->assertSame(['display' => 'calendar'], $field->configuration);
        $this->actingAs($secretary)
            ->get(route('kelurahan.letter-type-versions.show', $draft))
            ->assertOk()
            ->assertSee('Tanggal Keperluan');

        $this->actingAs($secretary)
            ->put(route('kelurahan.letter-type-versions.fields.update', [$draft, $field]), [
                'key' => 'tanggal_keperluan',
                'label' => 'Tanggal Penggunaan',
                'field_type' => LetterFieldType::DATE->value,
                'sequence' => 10,
                'is_required' => '1',
                'data_source' => LetterFieldDataSource::CITIZEN->value,
                'validation' => '{"after_or_equal":"today"}',
                'configuration' => '{"display":"calendar"}',
            ])->assertRedirect();
        $this->assertSame('Tanggal Penggunaan', $field->fresh()->label);

        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.fields.store', $draft), [
                'key' => 'tanggal_keperluan',
                'label' => 'Duplicate',
                'field_type' => LetterFieldType::TEXT->value,
                'sequence' => 20,
                'is_required' => '0',
            ])->assertSessionHasErrors('key');

        try {
            app(LetterTypeConfigurationService::class)->createField($draft, [
                'key' => 'pilihan_layanan',
                'label' => 'Pilihan Layanan',
                'field_type' => LetterFieldType::SELECT->value,
                'sequence' => 20,
                'is_required' => false,
                'data_source' => null,
                'validation' => null,
                'configuration' => null,
            ]);
            $this->fail('Select without options unexpectedly passed the service boundary.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('configuration', $exception->errors());
        }
        $select = $draft->fieldDefinitions()->create([
            'key' => 'pilihan_layanan',
            'label' => 'Pilihan Layanan',
            'field_type' => LetterFieldType::SELECT->value,
            'sequence' => 20,
            'is_required' => false,
            'data_source' => null,
            'validation' => null,
            'configuration' => null,
        ]);
        $temporary = app(LetterTypeConfigurationService::class)->createField($draft, [
            'key' => 'temporary_flag',
            'label' => 'Temporary Flag',
            'field_type' => LetterFieldType::BOOLEAN->value,
            'sequence' => 30,
            'is_required' => false,
            'data_source' => null,
            'validation' => null,
            'configuration' => null,
        ]);
        $this->actingAs($secretary)
            ->delete(route('kelurahan.letter-type-versions.fields.destroy', [$draft, $temporary]))
            ->assertRedirect();
        $this->assertDatabaseMissing('letter_field_definitions', ['id' => $temporary->id]);
        $this->makePublishable($draft);

        try {
            app(LetterTypeVersionService::class)->publish($draft);
            $this->fail('Select without options unexpectedly published.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('configuration.options', implode(' ', $exception->errors()['configuration']));
        }

        app(LetterTypeConfigurationService::class)->updateField($draft, $select, [
            'configuration' => ['options' => [' A ', 'B']],
        ]);
        $this->assertSame(['A', 'B'], $select->fresh()->configuration['options']);
        app(LetterTypeVersionService::class)->publish($draft);

        $this->actingAs($secretary)
            ->delete(route('kelurahan.letter-type-versions.fields.destroy', [$draft, $field]))
            ->assertForbidden();
        $this->actingAs($secretary)
            ->put(route('kelurahan.letter-type-versions.fields.update', [$draft, $field]), [
                'key' => 'tanggal_keperluan',
                'label' => 'Published Mutation',
                'field_type' => LetterFieldType::DATE->value,
                'sequence' => 10,
                'is_required' => '1',
                'data_source' => LetterFieldDataSource::CITIZEN->value,
            ])->assertForbidden();
        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.fields.store', $draft), [
                'key' => 'published_insert',
                'label' => 'Published Insert',
                'field_type' => LetterFieldType::TEXT->value,
                'sequence' => 30,
                'is_required' => '0',
            ])->assertForbidden();
        $this->assertDatabaseHas('letter_field_definitions', ['id' => $field->id]);
    }

    public function test_unconfigured_requirement_blocks_publish_without_inventing_evidence_semantics(): void
    {
        [, , $draft] = $this->draft('UNKNOWN_EVIDENCE');
        app(LetterTypeConfigurationService::class)->createRequirement($draft, [
            'key' => 'KTP',
            'label' => 'KTP',
            'description' => 'Cara pembuktian belum dipastikan.',
            'is_required' => true,
            'evidence_type' => LetterRequirementEvidenceType::UNCONFIGURED->value,
            'sequence' => 10,
            'configuration' => null,
        ]);
        $this->makePublishable($draft);

        $this->expectException(ValidationException::class);
        app(LetterTypeVersionService::class)->publish($draft);
    }

    public function test_workflows_without_territory_with_rt_only_and_with_rt_then_rw_are_publishable(): void
    {
        $flows = [
            'FLOW_C_NO_TERRITORY' => [],
            'FLOW_A_RT_ONLY' => [LetterWorkflowActorScope::RT],
            'FLOW_B_RT_THEN_RW' => [LetterWorkflowActorScope::RT, LetterWorkflowActorScope::RW],
        ];

        foreach ($flows as $code => $regionalScopes) {
            [, , $draft] = $this->draft($code);
            $configuration = app(LetterTypeConfigurationService::class);
            $sequence = 10;
            $configuration->createWorkflowStep($draft, $this->step($sequence, LetterWorkflowAction::SUBMIT, LetterWorkflowActorScope::CITIZEN));

            foreach ($regionalScopes as $scope) {
                $sequence += 10;
                $configuration->createWorkflowStep($draft, $this->step($sequence, LetterWorkflowAction::VERIFY, $scope));
            }

            foreach ([
                [LetterWorkflowAction::APPROVE, VillagePosition::VILLAGE_SECRETARY],
                [LetterWorkflowAction::SIGN, VillagePosition::VILLAGE_HEAD],
                [LetterWorkflowAction::ISSUE, VillagePosition::VILLAGE_SECRETARY],
            ] as [$action, $position]) {
                $sequence += 10;
                $configuration->createWorkflowStep($draft, $this->step(
                    $sequence,
                    $action,
                    LetterWorkflowActorScope::KELURAHAN,
                    $position,
                ));
            }

            $published = app(LetterTypeVersionService::class)->publish($draft);
            $this->assertSame(LetterTypeVersionStatus::PUBLISHED, $published->status);
            $expected = [[
                'action' => LetterWorkflowAction::SUBMIT->value,
                'scope' => LetterWorkflowActorScope::CITIZEN->value,
                'position' => null,
                'role' => null,
            ]];
            foreach ($regionalScopes as $scope) {
                $expected[] = [
                    'action' => LetterWorkflowAction::VERIFY->value,
                    'scope' => $scope->value,
                    'position' => null,
                    'role' => $scope === LetterWorkflowActorScope::RT ? UserRole::RT->value : UserRole::RW->value,
                ];
            }
            $expected = [...$expected,
                [
                    'action' => LetterWorkflowAction::APPROVE->value,
                    'scope' => LetterWorkflowActorScope::KELURAHAN->value,
                    'position' => VillagePosition::VILLAGE_SECRETARY->value,
                    'role' => UserRole::KELURAHAN->value,
                ],
                [
                    'action' => LetterWorkflowAction::SIGN->value,
                    'scope' => LetterWorkflowActorScope::KELURAHAN->value,
                    'position' => VillagePosition::VILLAGE_HEAD->value,
                    'role' => UserRole::KELURAHAN->value,
                ],
                [
                    'action' => LetterWorkflowAction::ISSUE->value,
                    'scope' => LetterWorkflowActorScope::KELURAHAN->value,
                    'position' => VillagePosition::VILLAGE_SECRETARY->value,
                    'role' => UserRole::KELURAHAN->value,
                ],
            ];
            $this->assertSame($expected, $published->workflowSteps->map(static fn ($step): array => [
                'action' => $step->action->value,
                'scope' => $step->actor_scope->value,
                'position' => $step->village_position?->value,
                'role' => $step->actor_role?->value,
            ])->all());
        }
    }

    public function test_workflow_request_rejects_duplicate_sequence_and_invalid_actor_action(): void
    {
        [$secretary, , $draft] = $this->draft('WORKFLOW_VALIDATION');
        $valid = [
            'sequence' => 10,
            'action' => LetterWorkflowAction::VERIFY->value,
            'actor_scope' => LetterWorkflowActorScope::RT->value,
            'village_position' => null,
            'is_required' => '1',
        ];
        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.workflow.store', $draft), $valid)
            ->assertRedirect();
        $step = $draft->workflowSteps()->sole();
        $this->actingAs($secretary)
            ->put(route('kelurahan.letter-type-versions.workflow.update', [$draft, $step]), [
                ...$valid,
                'sequence' => 15,
                'actor_scope' => LetterWorkflowActorScope::RT->value,
            ])->assertRedirect();
        $this->assertSame(15, $step->fresh()->sequence);
        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.workflow.store', $draft), $valid)
            ->assertRedirect();
        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.workflow.store', $draft), [
                ...$valid,
                'sequence' => 10,
            ])->assertSessionHasErrors('sequence');
        $temporary = $draft->workflowSteps()->where('sequence', 10)->sole();
        $this->actingAs($secretary)
            ->delete(route('kelurahan.letter-type-versions.workflow.destroy', [$draft, $temporary]))
            ->assertRedirect();
        $this->assertDatabaseMissing('letter_workflow_steps', ['id' => $temporary->id]);
        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.workflow.store', $draft), [
                ...$valid,
                'sequence' => 20,
                'action' => LetterWorkflowAction::SIGN->value,
                'actor_scope' => LetterWorkflowActorScope::RT->value,
            ])->assertSessionHasErrors('actor_scope');
        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.workflow.store', $draft), [
                ...$valid,
                'sequence' => 30,
                'action' => 'EXECUTE_PHP',
            ])->assertSessionHasErrors('action');

        $configuration = app(LetterTypeConfigurationService::class);
        $configuration->createWorkflowStep($draft, $this->step(
            5,
            LetterWorkflowAction::SUBMIT,
            LetterWorkflowActorScope::CITIZEN,
        ));
        $configuration->createWorkflowStep($draft, $this->step(
            20,
            LetterWorkflowAction::APPROVE,
            LetterWorkflowActorScope::KELURAHAN,
            VillagePosition::VILLAGE_SECRETARY,
        ));
        $configuration->createWorkflowStep($draft, $this->step(
            25,
            LetterWorkflowAction::SIGN,
            LetterWorkflowActorScope::KELURAHAN,
            VillagePosition::VILLAGE_HEAD,
        ));
        $configuration->createWorkflowStep($draft, $this->step(
            30,
            LetterWorkflowAction::ISSUE,
            LetterWorkflowActorScope::KELURAHAN,
            VillagePosition::VILLAGE_SECRETARY,
        ));
        app(LetterTypeVersionService::class)->publish($draft);
        $this->actingAs($secretary)
            ->put(route('kelurahan.letter-type-versions.workflow.update', [$draft, $step]), [
                ...$valid,
                'sequence' => 15,
                'actor_scope' => LetterWorkflowActorScope::RT->value,
            ])->assertForbidden();
        $this->actingAs($secretary)
            ->delete(route('kelurahan.letter-type-versions.workflow.destroy', [$draft, $step]))
            ->assertForbidden();
        $this->actingAs($secretary)
            ->post(route('kelurahan.letter-type-versions.workflow.store', $draft), $valid)
            ->assertForbidden();
    }

    public function test_publish_rejects_structurally_impossible_workflows(): void
    {
        $submit = fn (int $sequence, bool $required = true): array => $this->rawStep(
            $sequence,
            LetterWorkflowAction::SUBMIT,
            LetterWorkflowActorScope::CITIZEN,
            null,
            $required,
        );
        $verify = fn (int $sequence, LetterWorkflowActorScope $scope): array => $this->rawStep(
            $sequence,
            LetterWorkflowAction::VERIFY,
            $scope,
        );
        $approve = fn (
            int $sequence,
            LetterWorkflowActorScope $scope = LetterWorkflowActorScope::KELURAHAN,
            ?VillagePosition $position = VillagePosition::VILLAGE_SECRETARY,
        ): array => $this->rawStep($sequence, LetterWorkflowAction::APPROVE, $scope, $position);
        $sign = fn (
            int $sequence,
            LetterWorkflowActorScope $scope = LetterWorkflowActorScope::KELURAHAN,
            ?VillagePosition $position = VillagePosition::VILLAGE_HEAD,
        ): array => $this->rawStep($sequence, LetterWorkflowAction::SIGN, $scope, $position);
        $issue = fn (int $sequence, bool $required = true, ?VillagePosition $position = VillagePosition::VILLAGE_SECRETARY): array => $this->rawStep(
            $sequence,
            LetterWorkflowAction::ISSUE,
            LetterWorkflowActorScope::KELURAHAN,
            $position,
            $required,
        );

        $cases = [
            'missing_submit' => [$approve(20), $sign(30), $issue(40)],
            'submit_not_first' => [$verify(10, LetterWorkflowActorScope::RT), $submit(20), $approve(30), $sign(40), $issue(50)],
            'duplicate_submit' => [$submit(10), $submit(15), $approve(20), $sign(30), $issue(40)],
            'optional_submit' => [$submit(10, false), $approve(20), $sign(30), $issue(40)],
            'optional_rt_verify' => [$submit(10), $this->rawStep(15, LetterWorkflowAction::VERIFY, LetterWorkflowActorScope::RT, null, false), $approve(20), $sign(30), $issue(40)],
            'intermediate_issue' => [$submit(10), $issue(20), $approve(30), $sign(40)],
            'duplicate_issue' => [$submit(10), $approve(20), $sign(30), $issue(40), $issue(50)],
            'optional_terminal_issue' => [$submit(10), $approve(20), $sign(30), $issue(40, false)],
            'missing_approve' => [$submit(10), $sign(30), $issue(40)],
            'wrong_approve_actor' => [$submit(10), $approve(20, LetterWorkflowActorScope::RT, null), $sign(30), $issue(40)],
            'wrong_approve_position' => [$submit(10), $approve(20, LetterWorkflowActorScope::KELURAHAN, VillagePosition::VILLAGE_HEAD), $sign(30), $issue(40)],
            'missing_sign' => [$submit(10), $approve(20), $issue(40)],
            'duplicate_sign' => [$submit(10), $approve(20), $sign(30), $sign(35), $issue(40)],
            'wrong_sign_actor' => [$submit(10), $approve(20), $sign(30, LetterWorkflowActorScope::RW, null), $issue(40)],
            'wrong_sign_position' => [$submit(10), $approve(20), $sign(30, LetterWorkflowActorScope::KELURAHAN, VillagePosition::VILLAGE_SECRETARY), $issue(40)],
            'verify_after_approve' => [$submit(10), $approve(20), $verify(30, LetterWorkflowActorScope::RT), $sign(40), $issue(50)],
            'rw_without_rt' => [$submit(10), $verify(20, LetterWorkflowActorScope::RW), $approve(30), $sign(40), $issue(50)],
            'rw_before_rt' => [$submit(10), $verify(20, LetterWorkflowActorScope::RW), $verify(30, LetterWorkflowActorScope::RT), $approve(40), $sign(50), $issue(60)],
            'wrong_issue_position' => [$submit(10), $approve(20), $sign(30), $issue(40, true, VillagePosition::VILLAGE_HEAD)],
            'system_admin_actor' => [
                $submit(10),
                [...$approve(20, LetterWorkflowActorScope::KELURAHAN, VillagePosition::SYSTEM_ADMIN), 'actor_role' => UserRole::ADMIN->value],
                $sign(30),
                $issue(40),
            ],
        ];

        foreach ($cases as $name => $steps) {
            [, , $draft] = $this->draft('INVALID_'.mb_strtoupper($name));
            foreach ($steps as $step) {
                $draft->workflowSteps()->create($step);
            }

            try {
                app(LetterTypeVersionService::class)->publish($draft);
                $this->fail("Invalid workflow {$name} unexpectedly published.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('configuration', $exception->errors(), $name);
            }
        }
    }

    public function test_select_options_are_rejected_at_request_and_publish_boundaries(): void
    {
        $cases = [
            'empty' => [],
            'null' => [null],
            'boolean' => [true],
            'numeric' => [1],
            'nested' => [[]],
            'object' => [['value' => 'A']],
            'duplicate_after_trim' => ['A', ' A '],
            'blank' => ['   '],
        ];

        [$secretary, , $requestDraft] = $this->draft('SELECT_REQUEST_GUARD');
        $sequence = 10;
        foreach ($cases as $name => $options) {
            $this->actingAs($secretary)
                ->post(route('kelurahan.letter-type-versions.fields.store', $requestDraft), [
                    'key' => "invalid_select_{$name}",
                    'label' => "Invalid Select {$name}",
                    'field_type' => LetterFieldType::SELECT->value,
                    'sequence' => $sequence,
                    'is_required' => '0',
                    'configuration' => json_encode(['options' => $options], JSON_THROW_ON_ERROR),
                ])
                ->assertSessionHasErrors('configuration');
            $sequence += 10;
        }
        $this->assertSame(0, $requestDraft->fieldDefinitions()->count());

        foreach ($cases as $name => $options) {
            [, , $publishDraft] = $this->draft('SELECT_PUBLISH_GUARD_'.mb_strtoupper($name));
            $publishDraft->fieldDefinitions()->create([
                'key' => 'invalid_select',
                'label' => 'Invalid Select',
                'field_type' => LetterFieldType::SELECT->value,
                'sequence' => 10,
                'is_required' => false,
                'data_source' => null,
                'validation' => null,
                'configuration' => ['options' => $options],
            ]);
            $this->makePublishable($publishDraft);

            try {
                app(LetterTypeVersionService::class)->publish($publishDraft);
                $this->fail("Malformed select case {$name} unexpectedly published.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('configuration', $exception->errors());
            }
        }
    }

    public function test_published_children_reject_direct_model_create_update_and_delete(): void
    {
        [, , $draft] = $this->draft('PUBLISHED_CHILD_MODEL_GUARD');
        $configuration = app(LetterTypeConfigurationService::class);
        $requirement = $configuration->createRequirement($draft, [
            'key' => 'KTP',
            'label' => 'Kartu Tanda Penduduk',
            'description' => null,
            'is_required' => true,
            'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
            'sequence' => 10,
            'configuration' => null,
        ]);
        $field = $configuration->createField($draft, [
            'key' => 'purpose',
            'label' => 'Purpose',
            'field_type' => LetterFieldType::TEXT->value,
            'sequence' => 10,
            'is_required' => true,
            'data_source' => null,
            'validation' => null,
            'configuration' => null,
        ]);
        $this->makePublishable($draft);
        $workflow = $draft->workflowSteps()->orderBy('sequence')->firstOrFail();
        app(LetterTypeVersionService::class)->publish($draft);

        $createAttempts = [
            'requirement' => fn () => $draft->requirements()->create([
                'key' => 'KK',
                'label' => 'Kartu Keluarga',
                'is_required' => true,
                'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
                'sequence' => 20,
            ]),
            'field' => fn () => $draft->fieldDefinitions()->create([
                'key' => 'notes',
                'label' => 'Notes',
                'field_type' => LetterFieldType::TEXT->value,
                'is_required' => false,
                'sequence' => 20,
            ]),
            'workflow' => fn () => $draft->workflowSteps()->create($this->rawStep(
                50,
                LetterWorkflowAction::VERIFY,
                LetterWorkflowActorScope::RT,
            )),
        ];
        foreach ($createAttempts as $name => $attempt) {
            $this->assertValidationRejected(
                $attempt,
                'configuration',
                "Direct {$name} creation unexpectedly mutated a published version.",
            );
        }

        $updateAttempts = [
            'requirement' => fn () => $requirement->fresh()->update(['label' => 'Mutated requirement']),
            'field' => fn () => $field->fresh()->update(['label' => 'Mutated field']),
            'workflow' => fn () => $workflow->fresh()->update(['configuration' => ['mutated' => true]]),
        ];
        foreach ($updateAttempts as $name => $attempt) {
            $this->assertValidationRejected(
                $attempt,
                'configuration',
                "Direct {$name} update unexpectedly mutated a published version.",
            );
        }

        $deleteAttempts = [
            'requirement' => fn () => $requirement->fresh()->delete(),
            'field' => fn () => $field->fresh()->delete(),
            'workflow' => fn () => $workflow->fresh()->delete(),
        ];
        foreach ($deleteAttempts as $name => $attempt) {
            $this->assertValidationRejected(
                $attempt,
                'configuration',
                "Direct {$name} deletion unexpectedly mutated a published version.",
            );
        }

        $this->assertDatabaseHas('letter_requirements', [
            'id' => $requirement->id,
            'label' => 'Kartu Tanda Penduduk',
        ]);
        $this->assertDatabaseHas('letter_field_definitions', [
            'id' => $field->id,
            'label' => 'Purpose',
        ]);
        $this->assertDatabaseHas('letter_workflow_steps', [
            'id' => $workflow->id,
            'configuration' => null,
        ]);
    }

    public function test_publish_revalidates_raw_requirement_and_field_identity(): void
    {
        $requirementCases = [
            'malformed_key' => ['key' => 'invalid-key', 'label' => 'Identitas'],
            'blank_label' => ['key' => 'IDENTITAS', 'label' => '   '],
        ];
        foreach ($requirementCases as $name => $identity) {
            [, , $draft] = $this->draft('RAW_REQUIREMENT_IDENTITY_'.mb_strtoupper($name));
            $draft->requirements()->create([
                ...$identity,
                'description' => null,
                'is_required' => true,
                'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
                'sequence' => 10,
                'configuration' => null,
            ]);
            $this->makePublishable($draft);

            $this->assertValidationRejected(
                fn () => app(LetterTypeVersionService::class)->publish($draft),
                'configuration',
                "Raw requirement identity case {$name} unexpectedly published.",
            );
        }

        $fieldCases = [
            'malformed_key' => ['key' => 'INVALID_FIELD', 'label' => 'Field'],
            'blank_label' => ['key' => 'valid_field', 'label' => '   '],
        ];
        foreach ($fieldCases as $name => $identity) {
            [, , $draft] = $this->draft('RAW_FIELD_IDENTITY_'.mb_strtoupper($name));
            $draft->fieldDefinitions()->create([
                ...$identity,
                'field_type' => LetterFieldType::TEXT->value,
                'is_required' => false,
                'sequence' => 10,
                'data_source' => null,
                'validation' => null,
                'configuration' => null,
            ]);
            $this->makePublishable($draft);

            $this->assertValidationRejected(
                fn () => app(LetterTypeVersionService::class)->publish($draft),
                'configuration',
                "Raw field identity case {$name} unexpectedly published.",
            );
        }
    }

    public function test_configuration_service_rejects_parent_relocation(): void
    {
        [, , $source] = $this->draft('RELOCATION_SOURCE');
        [, , $publishedTarget] = $this->draft('RELOCATION_PUBLISHED');
        [, , $draftTarget] = $this->draft('RELOCATION_DRAFT');
        $service = app(LetterTypeConfigurationService::class);
        $requirement = $service->createRequirement($source, [
            'key' => 'KTP',
            'label' => 'KTP',
            'description' => null,
            'is_required' => true,
            'evidence_type' => LetterRequirementEvidenceType::MASTER_DATA->value,
            'sequence' => 10,
            'configuration' => null,
        ]);
        $field = $service->createField($source, [
            'key' => 'purpose',
            'label' => 'Purpose',
            'field_type' => LetterFieldType::TEXT->value,
            'sequence' => 10,
            'is_required' => true,
            'data_source' => null,
            'validation' => null,
            'configuration' => null,
        ]);
        $workflow = $service->createWorkflowStep($source, $this->step(
            10,
            LetterWorkflowAction::SUBMIT,
            LetterWorkflowActorScope::CITIZEN,
        ));
        $this->makePublishable($publishedTarget);
        app(LetterTypeVersionService::class)->publish($publishedTarget);

        $attempts = [
            fn (LetterTypeVersion $target) => $service->updateRequirement($source, $requirement, ['letter_type_version_id' => $target->id]),
            fn (LetterTypeVersion $target) => $service->updateField($source, $field, ['letter_type_version_id' => $target->id]),
            fn (LetterTypeVersion $target) => $service->updateWorkflowStep($source, $workflow, ['letter_type_version_id' => $target->id]),
        ];
        foreach ([$draftTarget, $publishedTarget] as $target) {
            foreach ($attempts as $attempt) {
                try {
                    $attempt($target);
                    $this->fail('Configuration child unexpectedly moved between versions.');
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey('letter_type_version_id', $exception->errors());
                }
            }
        }

        try {
            $requirement->update(['letter_type_version_id' => $draftTarget->id]);
            $this->fail('Direct model update unexpectedly changed the configuration parent.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('letter_type_version_id', $exception->errors());
        }

        $this->assertSame($source->id, $requirement->fresh()->letter_type_version_id);
        $this->assertSame($source->id, $field->fresh()->letter_type_version_id);
        $this->assertSame($source->id, $workflow->fresh()->letter_type_version_id);
    }

    /** @return array{User, LetterTypeDefinition, LetterTypeVersion} */
    private function draft(string $code): array
    {
        $secretary = User::factory()->create([
            'role' => UserRole::KELURAHAN,
            'position' => VillagePosition::VILLAGE_SECRETARY,
            'rw_id' => null,
            'rt_id' => null,
        ]);
        [$letterType, $draft] = app(LetterTypeDefinitionService::class)->createWithDraft([
            'code' => $code,
            'name' => "Surat {$code}",
            'description' => null,
            'is_active' => true,
        ], $secretary);

        return [$secretary, $letterType, $draft];
    }

    private function makePublishable(LetterTypeVersion $draft): void
    {
        if ($draft->workflowSteps()->exists()) {
            return;
        }

        $configuration = app(LetterTypeConfigurationService::class);
        foreach ([
            $this->step(10, LetterWorkflowAction::SUBMIT, LetterWorkflowActorScope::CITIZEN),
            $this->step(20, LetterWorkflowAction::APPROVE, LetterWorkflowActorScope::KELURAHAN, VillagePosition::VILLAGE_SECRETARY),
            $this->step(30, LetterWorkflowAction::SIGN, LetterWorkflowActorScope::KELURAHAN, VillagePosition::VILLAGE_HEAD),
            $this->step(40, LetterWorkflowAction::ISSUE, LetterWorkflowActorScope::KELURAHAN, VillagePosition::VILLAGE_SECRETARY),
        ] as $step) {
            $configuration->createWorkflowStep($draft, $step);
        }
    }

    private function step(
        int $sequence,
        LetterWorkflowAction $action,
        LetterWorkflowActorScope $scope,
        ?VillagePosition $position = null,
    ): array {
        return [
            'sequence' => $sequence,
            'action' => $action->value,
            'actor_scope' => $scope->value,
            'village_position' => $position?->value,
            'is_required' => true,
            'configuration' => null,
        ];
    }

    private function rawStep(
        int $sequence,
        LetterWorkflowAction $action,
        LetterWorkflowActorScope $scope,
        ?VillagePosition $position = null,
        bool $required = true,
    ): array {
        $role = match ($scope) {
            LetterWorkflowActorScope::CITIZEN => null,
            LetterWorkflowActorScope::RT => UserRole::RT->value,
            LetterWorkflowActorScope::RW => UserRole::RW->value,
            LetterWorkflowActorScope::KELURAHAN => UserRole::KELURAHAN->value,
        };

        return [
            'sequence' => $sequence,
            'action' => $action->value,
            'actor_scope' => $scope->value,
            'actor_role' => $role,
            'village_position' => $position?->value,
            'is_required' => $required,
            'configuration' => null,
        ];
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
