<?php

namespace Tests\Feature;

use App\Context\CitizenRequestUnderstanding;
use App\Context\EntryContext;
use App\Context\ServiceEligibilityDecision;
use App\Enums\CapabilityRequirement;
use App\Enums\CitizenIntent;
use App\Enums\InformationAccessLevel;
use App\Enums\InformationCategory;
use App\Enums\InformationClassificationReason;
use App\Enums\MissingServiceRequirement;
use App\Enums\ServiceEligibilityReason;
use App\Enums\ServiceEligibilityStatus;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\CitizenRequestInterpreter;
use App\Services\RuleBasedInformationAccessClassifier;
use App\Services\RuleBasedIntentResolver;
use App\Services\ServiceEligibilityPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InformationAccessClassificationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('publicInformationMessages')]
    public function test_public_information_is_classified_for_anonymous_access(
        string $message,
        InformationCategory $category,
    ): void {
        $classification = $this->classifier()->classify($message);

        $this->assertSame(CitizenIntent::INFORMATION, app(RuleBasedIntentResolver::class)->resolve($message)->intent);
        $this->assertSame(InformationAccessLevel::PUBLIC, $classification->accessLevel);
        $this->assertSame($category, $classification->category);
        $this->assertSame(CapabilityRequirement::OPTIONAL, $classification->identityRequirement);
        $this->assertSame(InformationClassificationReason::PUBLIC_RULE_MATCHED, $classification->reason);
        $this->assertTrue($classification->allowsAnonymousAccess());
    }

    /** @return iterable<string, array{string, InformationCategory}> */
    public static function publicInformationMessages(): iterable
    {
        yield 'service hours' => ['kantor desa buka jam berapa', InformationCategory::SERVICE_HOURS];
        yield 'ambulance number' => ['nomor ambulans desa berapa', InformationCategory::PUBLIC_CONTACT];
        yield 'posyandu schedule' => ['jadwal posyandu kapan', InformationCategory::PUBLIC_SCHEDULE];
        yield 'letter requirements' => ['syarat surat domisili apa', InformationCategory::SERVICE_REQUIREMENTS];
        yield 'letter procedure' => ['bagaimana cara mengurus surat pengantar', InformationCategory::SERVICE_PROCEDURE];
        yield 'official fee' => ['berapa biaya resmi pembuatan surat', InformationCategory::OFFICIAL_FEE];
    }

    #[DataProvider('protectedInformationMessages')]
    public function test_protected_information_requires_identity_and_future_authorization(
        string $message,
        InformationCategory $category,
    ): void {
        $classification = $this->classifier()->classify($message);

        $this->assertSame(CitizenIntent::INFORMATION, app(RuleBasedIntentResolver::class)->resolve($message)->intent);
        $this->assertSame(InformationAccessLevel::PROTECTED, $classification->accessLevel);
        $this->assertSame($category, $classification->category);
        $this->assertSame(CapabilityRequirement::REQUIRED, $classification->identityRequirement);
        $this->assertSame(InformationClassificationReason::PROTECTED_RULE_MATCHED, $classification->reason);
        $this->assertFalse($classification->allowsAnonymousAccess());
    }

    /** @return iterable<string, array{string, InformationCategory}> */
    public static function protectedInformationMessages(): iterable
    {
        yield 'citizen NIK' => ['NIK Budi berapa', InformationCategory::CITIZEN_DATA];
        yield 'family members' => ['siapa saja anggota KK nomor 123', InformationCategory::FAMILY_DATA];
        yield 'personal report status' => ['laporan Budi statusnya apa', InformationCategory::PERSONAL_REPORT_STATUS];
        yield 'personal letter status' => ['surat milik Andi sudah selesai belum', InformationCategory::PERSONAL_LETTER_STATUS];
        yield 'census data' => ['tampilkan data sensus RT 01', InformationCategory::CENSUS_DATA];
    }

    public function test_ambiguous_citizen_information_defaults_to_protected(): void
    {
        $classification = $this->classifier()->classify('kasih saya informasi tentang warga');

        $this->assertSame(
            CitizenIntent::INFORMATION,
            app(RuleBasedIntentResolver::class)->resolve('kasih saya informasi tentang warga')->intent,
        );
        $this->assertSame(InformationAccessLevel::PROTECTED, $classification->accessLevel);
        $this->assertSame(InformationCategory::UNKNOWN_PROTECTED, $classification->category);
        $this->assertSame(InformationClassificationReason::DEFAULT_PROTECTED, $classification->reason);
        $this->assertFalse($classification->allowsAnonymousAccess());
    }

    public function test_public_classification_does_not_mutate_citizen_or_report(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create();
        Report::factory()->for($citizen)->for($rt)->create();
        $citizenBefore = DB::table('citizens')->find($citizen->id);
        $reportsBefore = DB::table('reports')->get()->all();

        $this->classifier()->classify('nomor ambulans desa berapa');

        $this->assertEquals($citizenBefore, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($reportsBefore, DB::table('reports')->get()->all());
    }

    public function test_protected_classification_performs_no_data_lookup(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $classification = $this->classifier()->classify('laporan Budi statusnya apa');

        $this->assertSame(InformationCategory::PERSONAL_REPORT_STATUS, $classification->category);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_public_and_protected_requests_share_information_intent_but_not_anonymous_eligibility(): void
    {
        $public = $this->interpretAndEvaluate('nomor ambulans desa berapa');
        $protected = $this->interpretAndEvaluate('NIK Budi berapa');

        $this->assertSame(CitizenIntent::INFORMATION, $public['understanding']->ruleBasedResolution->resolution->intent);
        $this->assertSame(ServiceEligibilityStatus::ELIGIBLE, $public['eligibility']->status);
        $this->assertSame(InformationCategory::PUBLIC_CONTACT, $public['eligibility']->informationAccessClassification?->category);

        $this->assertSame(CitizenIntent::INFORMATION, $protected['understanding']->ruleBasedResolution->resolution->intent);
        $this->assertSame(ServiceEligibilityStatus::BLOCKED, $protected['eligibility']->status);
        $this->assertSame(ServiceEligibilityReason::IDENTITY_REQUIRED, $protected['eligibility']->reason);
        $this->assertSame(MissingServiceRequirement::IDENTITY, $protected['eligibility']->missingRequirement);
        $this->assertSame(InformationCategory::CITIZEN_DATA, $protected['eligibility']->informationAccessClassification?->category);
    }

    public function test_known_identity_still_requires_authorization_for_protected_information(): void
    {
        $citizen = Citizen::factory()->for($this->createRt())->create([
            'phone_normalized' => '6281234567890',
        ]);
        $message = 'NIK Budi berapa';
        $understanding = app(CitizenRequestInterpreter::class)->interpret(
            new EntryContext('web', $message, $citizen->phone_normalized),
            $message,
        );

        $eligibility = app(ServiceEligibilityPolicy::class)->evaluate($understanding);

        $this->assertSame(ServiceEligibilityStatus::BLOCKED, $eligibility->status);
        $this->assertSame(ServiceEligibilityReason::AUTHORIZATION_REQUIRED, $eligibility->reason);
        $this->assertSame(MissingServiceRequirement::AUTHORIZATION, $eligibility->missingRequirement);
        $this->assertDatabaseCount('reports', 0);
    }

    /** @return array{understanding: CitizenRequestUnderstanding, eligibility: ServiceEligibilityDecision} */
    private function interpretAndEvaluate(string $message): array
    {
        $understanding = app(CitizenRequestInterpreter::class)->interpret(
            new EntryContext('web', $message, '6289999999999'),
            $message,
        );

        return [
            'understanding' => $understanding,
            'eligibility' => app(ServiceEligibilityPolicy::class)->evaluate($understanding),
        ];
    }

    private function classifier(): RuleBasedInformationAccessClassifier
    {
        return app(RuleBasedInformationAccessClassifier::class);
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
