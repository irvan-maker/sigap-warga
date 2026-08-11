<?php

namespace Tests\Feature;

use App\Context\ProtectedInformationAuthorizationContext;
use App\Context\ProtectedInformationSubject;
use App\Context\RequesterIdentityContext;
use App\Context\StaffScopeContext;
use App\Enums\AuthorizationContextReason;
use App\Enums\AuthorizationContextStatus;
use App\Enums\InformationCategory;
use App\Enums\InformationSubjectRelationship;
use App\Enums\RequesterIdentityType;
use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\ProtectedInformationAuthorizationContextFactory;
use App\Services\RuleBasedInformationAccessClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProtectedInformationAuthorizationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_information_context_is_not_applicable(): void
    {
        $context = $this->createContext(
            message: 'nomor ambulans desa berapa',
            requester: RequesterIdentityContext::unknown(),
        );

        $this->assertSame(AuthorizationContextStatus::NOT_APPLICABLE, $context->status);
        $this->assertSame(AuthorizationContextReason::PUBLIC_NOT_APPLICABLE, $context->reason);
        $this->assertNull($context->subject);
    }

    public function test_known_citizen_self_context_is_complete_without_authorization_decision(): void
    {
        $citizen = Citizen::factory()->make();
        $context = $this->createContext(
            message: 'NIK saya berapa',
            requester: RequesterIdentityContext::knownCitizen($citizen),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CITIZEN_DATA,
                relationship: InformationSubjectRelationship::SELF,
            ),
        );

        $this->assertComplete($context, InformationSubjectRelationship::SELF);
        $this->assertSame(RequesterIdentityType::KNOWN_CITIZEN, $context->requester->type);
        $this->assertSame($citizen, $context->requester->citizen);
        $this->assertFalse(method_exists($context, 'allowsAccess'));
        $this->assertFalse(method_exists($context, 'deniesAccess'));
    }

    public function test_other_citizen_context_preserves_claim_without_fetching_subject(): void
    {
        $context = $this->createContext(
            message: 'NIK Budi berapa',
            requester: RequesterIdentityContext::knownCitizen(Citizen::factory()->make()),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CITIZEN_DATA,
                relationship: InformationSubjectRelationship::OTHER_CITIZEN,
                opaqueIdentifier: 'subject-b',
            ),
        );

        $this->assertComplete($context, InformationSubjectRelationship::OTHER_CITIZEN);
        $this->assertSame('subject-b', $context->subject?->opaqueIdentifier);
        $this->assertObjectNotHasProperty('nik', $context->subject);
    }

    public function test_unknown_requester_protected_context_is_incomplete(): void
    {
        $context = $this->createContext(
            message: 'NIK Budi berapa',
            requester: RequesterIdentityContext::unknown(),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CITIZEN_DATA,
                relationship: InformationSubjectRelationship::OTHER_CITIZEN,
            ),
        );

        $this->assertSame(AuthorizationContextStatus::INCOMPLETE, $context->status);
        $this->assertSame(AuthorizationContextReason::REQUESTER_REQUIRED, $context->reason);
    }

    public function test_household_relationship_is_preserved_without_family_card_lookup(): void
    {
        DB::enableQueryLog();

        $context = $this->createContext(
            message: 'siapa anggota KK saya',
            requester: RequesterIdentityContext::knownCitizen(Citizen::factory()->make()),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::FAMILY_DATA,
                relationship: InformationSubjectRelationship::HOUSEHOLD_MEMBER,
                opaqueIdentifier: 'household-claim',
            ),
        );

        $this->assertComplete($context, InformationSubjectRelationship::HOUSEHOLD_MEMBER);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_staff_role_and_limited_scope_are_preserved_without_blanket_authorization(): void
    {
        $actor = User::factory()->make(['role' => UserRole::RT]);
        $scope = new StaffScopeContext(
            role: UserRole::RT,
            rwId: 5,
            rtId: 10,
        );
        $requester = RequesterIdentityContext::staff($actor, $scope);
        $context = $this->createContext(
            message: 'tampilkan data sensus RT 01',
            requester: $requester,
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CENSUS_DATA,
                relationship: InformationSubjectRelationship::GOVERNMENT_SCOPE,
                opaqueIdentifier: 'rt-01',
            ),
        );

        $this->assertComplete($context, InformationSubjectRelationship::GOVERNMENT_SCOPE);
        $this->assertSame(RequesterIdentityType::STAFF, $context->requester->type);
        $this->assertSame(UserRole::RT, $context->staffScope?->role);
        $this->assertSame(5, $context->staffScope?->rwId);
        $this->assertSame(10, $context->staffScope?->rtId);
        $this->assertFalse(method_exists($context, 'isAuthorized'));
    }

    public function test_missing_subject_and_relationship_have_typed_incomplete_reasons(): void
    {
        $requester = RequesterIdentityContext::knownCitizen(Citizen::factory()->make());
        $missingSubject = $this->createContext('NIK Budi berapa', $requester);
        $unknownRelationship = $this->createContext(
            message: 'NIK Budi berapa',
            requester: $requester,
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CITIZEN_DATA,
                relationship: InformationSubjectRelationship::UNKNOWN,
            ),
        );

        $this->assertSame(AuthorizationContextReason::SUBJECT_REQUIRED, $missingSubject->reason);
        $this->assertSame(AuthorizationContextStatus::INCOMPLETE, $missingSubject->status);
        $this->assertSame(AuthorizationContextReason::RELATIONSHIP_REQUIRED, $unknownRelationship->reason);
        $this->assertSame(AuthorizationContextStatus::INCOMPLETE, $unknownRelationship->status);
    }

    public function test_context_assembly_performs_no_query_or_model_mutation(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create();
        $citizenBefore = DB::table('citizens')->find($citizen->id);
        $rtBefore = DB::table('rts')->find($rt->id);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $context = $this->createContext(
            message: 'NIK saya berapa',
            requester: RequesterIdentityContext::knownCitizen($citizen),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CITIZEN_DATA,
                relationship: InformationSubjectRelationship::SELF,
            ),
            requesterTerritory: $rt,
        );

        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame($rt, $context->requesterTerritory);
        $this->assertEquals($citizenBefore, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($rtBefore, DB::table('rts')->find($rt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    private function createContext(
        string $message,
        RequesterIdentityContext $requester,
        ?ProtectedInformationSubject $subject = null,
        ?Rt $requesterTerritory = null,
    ): ProtectedInformationAuthorizationContext {
        $classification = app(RuleBasedInformationAccessClassifier::class)->classify($message);

        return app(ProtectedInformationAuthorizationContextFactory::class)->create(
            classification: $classification,
            requester: $requester,
            subject: $subject,
            requesterTerritory: $requesterTerritory,
        );
    }

    private function assertComplete(
        ProtectedInformationAuthorizationContext $context,
        InformationSubjectRelationship $relationship,
    ): void {
        $this->assertSame(AuthorizationContextStatus::COMPLETE, $context->status);
        $this->assertSame(AuthorizationContextReason::CONTEXT_COMPLETE, $context->reason);
        $this->assertSame($relationship, $context->subject?->relationship);
    }

    private function createRt(): Rt
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '001',
            'name' => 'RT 001',
        ]);
    }
}
