<?php

namespace Tests\Feature;

use App\Services\WhatsAppEntryReferenceParser;
use Tests\TestCase;

class WhatsAppEntryReferenceParserTest extends TestCase
{
    public function test_human_readable_entry_reference_is_extracted_without_leaving_technical_text(): void
    {
        $result = app(WhatsAppEntryReferenceParser::class)->extract(
            "MULAI LAPORAN SIGAP WARGA\n\nPintu layanan:\nRT-PILOT-01 / RW-PILOT-01",
        );

        $this->assertSame('MULAI LAPORAN SIGAP WARGA', $result->message);
        $this->assertSame('RT-PILOT-01', $result->rtCode);
        $this->assertSame('RW-PILOT-01', $result->rwCode);
        $this->assertFalse($result->ambiguous);
    }

    public function test_regular_report_message_is_preserved_without_an_entry_claim(): void
    {
        $result = app(WhatsAppEntryReferenceParser::class)->extract('jalan rusak di depan sekolah');

        $this->assertSame('jalan rusak di depan sekolah', $result->message);
        $this->assertNull($result->rtCode);
        $this->assertNull($result->rwCode);
    }
}
