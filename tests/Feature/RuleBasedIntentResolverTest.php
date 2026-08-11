<?php

namespace Tests\Feature;

use App\Context\IntentResolution;
use App\Enums\CitizenIntent;
use App\Enums\IntentRule;
use App\Enums\UrgencyLevel;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\IntentUrgencyPolicy;
use App\Services\RuleBasedIntentResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RuleBasedIntentResolverTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('emergencyMessages')]
    public function test_emergency_messages_have_emergency_precedence(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::EMERGENCY, UrgencyLevel::EMERGENCY);
    }

    /** @return iterable<string, array{string}> */
    public static function emergencyMessages(): iterable
    {
        yield 'ambulance request with punctuation' => ['  TOLONG, AMBULANS!!!  '];
        yield 'unconscious person' => ['ada orang pingsan'];
        yield 'fire' => ['kebakaran'];
        yield 'severe accident' => ['ada kecelakaan parah'];
    }

    #[DataProvider('informationMessages')]
    public function test_information_framing_avoids_domain_keyword_false_positives(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::INFORMATION, UrgencyLevel::NORMAL);
    }

    /** @return iterable<string, array{string}> */
    public static function informationMessages(): iterable
    {
        yield 'ambulance number' => ['nomor ambulans desa berapa'];
        yield 'ambulance schedule' => ['jadwal ambulans desa'];
        yield 'letter requirements' => ['syarat surat domisili apa'];
        yield 'office hours' => ['kantor desa buka jam berapa'];
    }

    #[DataProvider('normalReportMessages')]
    public function test_normal_report_messages(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::REPORT, UrgencyLevel::NORMAL);
    }

    /** @return iterable<string, array{string}> */
    public static function normalReportMessages(): iterable
    {
        yield 'damaged road' => ['jalan rusak'];
        yield 'street light' => ['lampu jalan mati'];
        yield 'garbage' => ['sampah menumpuk'];
    }

    #[DataProvider('highPriorityReportMessages')]
    public function test_high_priority_report_messages(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::REPORT, UrgencyLevel::HIGH);
    }

    /** @return iterable<string, array{string}> */
    public static function highPriorityReportMessages(): iterable
    {
        yield 'fallen tree blocks road' => ['pohon tumbang menutup jalan'];
        yield 'flood enters house' => ['banjir masuk rumah'];
        yield 'utility pole nearly collapses' => ['tiang listrik hampir roboh'];
    }

    #[DataProvider('letterMessages')]
    public function test_letter_messages(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::LETTER, UrgencyLevel::NORMAL);
    }

    /** @return iterable<string, array{string}> */
    public static function letterMessages(): iterable
    {
        yield 'domicile letter' => ['bikin surat domisili'];
        yield 'polite domicile letter' => ['buatkan surat domisili'];
        yield 'introduction letter' => ['surat pengantar'];
        yield 'business letter' => ['surat usaha'];
    }

    #[DataProvider('aspirationMessages')]
    public function test_aspiration_messages_override_report_keywords(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::ASPIRATION, UrgencyLevel::NORMAL);
    }

    /** @return iterable<string, array{string}> */
    public static function aspirationMessages(): iterable
    {
        yield 'street light proposal' => ['usul lampu jalan'];
        yield 'village park suggestion' => ['saran taman desa'];
    }

    #[DataProvider('unknownMessages')]
    public function test_ambiguous_greetings_remain_unknown(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::UNKNOWN, UrgencyLevel::NORMAL);
    }

    /** @return iterable<string, array{string}> */
    public static function unknownMessages(): iterable
    {
        yield 'greeting' => ['halo'];
        yield 'permission' => ['permisi'];
        yield 'test' => ['tes'];
    }

    #[DataProvider('negatedEmergencyMessages')]
    public function test_negated_emergency_phrases_remain_unknown(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::UNKNOWN, UrgencyLevel::NORMAL);
    }

    /** @return iterable<string, array{string}> */
    public static function negatedEmergencyMessages(): iterable
    {
        yield 'not a fire' => ['bukan kebakaran'];
        yield 'there is no fire' => ['tidak ada kebakaran'];
        yield 'does not need ambulance' => ['tidak butuh ambulans'];
        yield 'ambulance not required' => ['ambulans tidak diperlukan'];
        yield 'person did not faint' => ['orangnya tidak pingsan'];
    }

    #[DataProvider('colloquialEmergencyMessages')]
    public function test_real_colloquial_emergency_is_not_suppressed_by_negation_handling(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::EMERGENCY, UrgencyLevel::EMERGENCY);
    }

    /** @return iterable<string, array{string}> */
    public static function colloquialEmergencyMessages(): iterable
    {
        yield 'not conscious' => ['orang tidak sadar'];
        yield 'colloquial unconscious' => ['orang gak sadar'];
        yield 'direct ambulance request' => ['tolong panggil ambulans'];
        yield 'colloquial house fire' => ['rumah kebakar'];
    }

    #[DataProvider('ambulanceInformationMessages')]
    public function test_ambulance_information_questions_remain_information(string $message): void
    {
        $this->assertResolution($message, CitizenIntent::INFORMATION, UrgencyLevel::NORMAL);
    }

    /** @return iterable<string, array{string}> */
    public static function ambulanceInformationMessages(): iterable
    {
        yield 'contact number' => ['nomor ambulans berapa'];
        yield 'cost' => ['berapa biaya ambulans'];
        yield 'schedule' => ['jadwal ambulans'];
    }

    public function test_incident_territory_is_preserved_without_database_side_effects(): void
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $incidentRt = Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '010',
            'name' => 'RT 010',
        ]);

        $result = app(RuleBasedIntentResolver::class)->resolveWithExplanation(
            'jalan depan rumah rusak',
            $incidentRt,
        );

        $this->assertTrue($result->resolution->incidentRt?->is($incidentRt));
        $this->assertSame(IntentRule::REPORT_ROAD_DAMAGE, $result->matchedRule);
        $this->assertSame(CitizenIntent::REPORT, $result->matchedCategory);
        $this->assertSame('jalan depan rumah rusak', $result->matchedPhrase);
        $this->assertDatabaseCount('citizens', 0);
        $this->assertDatabaseCount('reports', 0);
    }

    private function assertResolution(
        string $message,
        CitizenIntent $intent,
        UrgencyLevel $urgency,
    ): void {
        $result = app(RuleBasedIntentResolver::class)->resolveWithExplanation($message);
        $resolution = $result->resolution;

        $this->assertSame($intent, $resolution->intent);
        $this->assertSame($urgency, $resolution->urgency);
        $this->assertSame($intent, $result->matchedCategory);
        $this->assertInstanceOf(IntentRule::class, $result->matchedRule);
        if ($intent === CitizenIntent::UNKNOWN) {
            $this->assertSame(IntentRule::UNKNOWN_NO_MATCH, $result->matchedRule);
            $this->assertNull($result->matchedPhrase);
        } else {
            $this->assertNotNull($result->matchedPhrase);
        }
        $this->assertTrue($this->validate($resolution));
    }

    private function validate(IntentResolution $resolution): bool
    {
        return app(IntentUrgencyPolicy::class)->validate($resolution)->isValid();
    }
}
