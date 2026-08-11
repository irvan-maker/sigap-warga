<?php

namespace Tests\Feature;

use App\Context\AuthorizationVerificationContext;
use App\Context\AuthorizationVerificationFacts;
use App\Context\ProtectedInformationAuthorizationContext;
use App\Context\ProtectedInformationSubject;
use App\Context\RequesterIdentityContext;
use App\Context\StaffScopeContext;
use App\Enums\InformationCategory;
use App\Enums\InformationSubjectRelationship;
use App\Enums\UserRole;
use App\Enums\VerificationState;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\AuthorizationVerificationContextFactory;
use App\Services\ProtectedInformationAuthorizationContextFactory;
use App\Services\RuleBasedInformationAccessClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthorizationVerificationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_information_has_no_applicable_verification_dimensions(): void
    {
        $context = $this->verification($this->authorization(
            message: 'nomor ambulans desa berapa',
            requester: RequesterIdentityContext::unknown(),
        ));

        $this->assertSame(VerificationState::NOT_APPLICABLE, $context->requester);
        $this->assertSame(VerificationState::NOT_APPLICABLE, $context->subject);
        $this->assertSame(VerificationState::NOT_APPLICABLE, $context->relationship);
        $this->assertSame(VerificationState::NOT_APPLICABLE, $context->staffScope);
        $this->assertTrue($context->isFullyVerifiedForAuthorization());
        $this->assertFalse($context->hasConflict());
    }

    public function test_protected_self_is_fully_verified_only_from_explicit_facts(): void
    {
        $authorization = $this->selfAuthorization();
        $context = $this->verification($authorization, new AuthorizationVerificationFacts(
            requester: VerificationState::VERIFIED,
            subject: VerificationState::VERIFIED,
            relationship: VerificationState::VERIFIED,
        ));

        $this->assertSame(VerificationState::VERIFIED, $context->requester);
        $this->assertSame(VerificationState::VERIFIED, $context->subject);
        $this->assertSame(VerificationState::VERIFIED, $context->relationship);
        $this->assertSame(VerificationState::NOT_APPLICABLE, $context->staffScope);
        $this->assertTrue($context->isFullyVerifiedForAuthorization());
        $this->assertFalse(method_exists($context, 'isAuthorized'));
        $this->assertFalse(method_exists($context, 'allowsAccess'));
    }

    public function test_known_identity_and_self_claim_remain_unverified_without_facts(): void
    {
        $context = $this->verification($this->selfAuthorization());

        $this->assertSame(VerificationState::UNVERIFIED, $context->requester);
        $this->assertSame(VerificationState::UNVERIFIED, $context->subject);
        $this->assertSame(VerificationState::UNVERIFIED, $context->relationship);
        $this->assertFalse($context->isFullyVerifiedForAuthorization());
        $this->assertFalse($context->hasConflict());
    }

    public function test_conflicting_self_relationship_is_preserved_and_not_fully_verified(): void
    {
        $context = $this->verification($this->selfAuthorization(), new AuthorizationVerificationFacts(
            requester: VerificationState::VERIFIED,
            subject: VerificationState::VERIFIED,
            relationship: VerificationState::CONFLICTING,
        ));

        $this->assertSame(VerificationState::CONFLICTING, $context->relationship);
        $this->assertTrue($context->hasConflict());
        $this->assertFalse($context->isFullyVerifiedForAuthorization());
    }

    public function test_household_claim_is_unverified_without_a_supplied_fact(): void
    {
        $authorization = $this->householdAuthorization();
        $context = $this->verification($authorization, new AuthorizationVerificationFacts(
            requester: VerificationState::VERIFIED,
            subject: VerificationState::VERIFIED,
        ));

        $this->assertSame(InformationSubjectRelationship::HOUSEHOLD_MEMBER, $authorization->subject?->relationship);
        $this->assertSame(VerificationState::UNVERIFIED, $context->relationship);
        $this->assertFalse($context->isFullyVerifiedForAuthorization());
    }

    public function test_household_relationship_can_preserve_caller_supplied_verified_fact(): void
    {
        $context = $this->verification($this->householdAuthorization(), new AuthorizationVerificationFacts(
            requester: VerificationState::VERIFIED,
            subject: VerificationState::VERIFIED,
            relationship: VerificationState::VERIFIED,
        ));

        $this->assertSame(VerificationState::VERIFIED, $context->relationship);
        $this->assertTrue($context->isFullyVerifiedForAuthorization());
    }

    public function test_staff_scope_is_unverified_until_explicitly_supplied(): void
    {
        $context = $this->verification($this->staffAuthorization());

        $this->assertSame(VerificationState::UNVERIFIED, $context->staffScope);
        $this->assertFalse($context->isFullyVerifiedForAuthorization());
    }

    public function test_staff_scope_can_be_explicitly_verified_without_permission_evaluation(): void
    {
        $context = $this->verification($this->staffAuthorization(), $this->verifiedStaffFacts(
            VerificationState::VERIFIED,
        ));

        $this->assertSame(VerificationState::VERIFIED, $context->staffScope);
        $this->assertTrue($context->isFullyVerifiedForAuthorization());
        $this->assertFalse(method_exists($context, 'hasPermission'));
    }

    public function test_conflicting_staff_scope_is_preserved(): void
    {
        $context = $this->verification($this->staffAuthorization(), $this->verifiedStaffFacts(
            VerificationState::CONFLICTING,
        ));

        $this->assertSame(VerificationState::CONFLICTING, $context->staffScope);
        $this->assertTrue($context->hasConflict());
        $this->assertFalse($context->isFullyVerifiedForAuthorization());
    }

    public function test_verification_assembly_performs_no_query_mutation_or_data_exposure(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create();
        $citizenBefore = DB::table('citizens')->find($citizen->id);
        $rtBefore = DB::table('rts')->find($rt->id);
        $authorization = $this->authorization(
            message: 'NIK saya berapa',
            requester: RequesterIdentityContext::knownCitizen($citizen),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CITIZEN_DATA,
                relationship: InformationSubjectRelationship::SELF,
                opaqueIdentifier: 'requester-subject',
            ),
            requesterTerritory: $rt,
        );
        DB::flushQueryLog();
        DB::enableQueryLog();

        $context = $this->verification($authorization, new AuthorizationVerificationFacts(
            requester: VerificationState::VERIFIED,
            subject: VerificationState::VERIFIED,
            relationship: VerificationState::VERIFIED,
        ));

        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertFalse(property_exists($context, 'citizen'));
        $this->assertFalse(property_exists($context, 'user'));
        $this->assertFalse(property_exists($context, 'nik'));
        $this->assertFalse(method_exists($context, 'toArray'));
        $this->assertEquals($citizenBefore, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($rtBefore, DB::table('rts')->find($rt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    private function selfAuthorization(): ProtectedInformationAuthorizationContext
    {
        return $this->authorization(
            message: 'NIK saya berapa',
            requester: RequesterIdentityContext::knownCitizen(Citizen::factory()->make()),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CITIZEN_DATA,
                relationship: InformationSubjectRelationship::SELF,
                opaqueIdentifier: 'self-subject',
            ),
        );
    }

    private function householdAuthorization(): ProtectedInformationAuthorizationContext
    {
        return $this->authorization(
            message: 'siapa anggota KK saya',
            requester: RequesterIdentityContext::knownCitizen(Citizen::factory()->make()),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::FAMILY_DATA,
                relationship: InformationSubjectRelationship::HOUSEHOLD_MEMBER,
                opaqueIdentifier: 'household-subject',
            ),
        );
    }

    private function staffAuthorization(): ProtectedInformationAuthorizationContext
    {
        $scope = new StaffScopeContext(role: UserRole::RT, rwId: 5, rtId: 10);

        return $this->authorization(
            message: 'tampilkan data sensus RT 01',
            requester: RequesterIdentityContext::staff(
                User::factory()->make(['role' => UserRole::RT, 'position' => null]),
                $scope,
            ),
            subject: new ProtectedInformationSubject(
                category: InformationCategory::CENSUS_DATA,
                relationship: InformationSubjectRelationship::GOVERNMENT_SCOPE,
                opaqueIdentifier: 'rt-10',
            ),
        );
    }

    private function verifiedStaffFacts(VerificationState $scope): AuthorizationVerificationFacts
    {
        return new AuthorizationVerificationFacts(
            requester: VerificationState::VERIFIED,
            subject: VerificationState::VERIFIED,
            relationship: VerificationState::VERIFIED,
            staffScope: $scope,
        );
    }

    private function authorization(
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

    private function verification(
        ProtectedInformationAuthorizationContext $authorization,
        ?AuthorizationVerificationFacts $facts = null,
    ): AuthorizationVerificationContext {
        return app(AuthorizationVerificationContextFactory::class)->create($authorization, $facts);
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
