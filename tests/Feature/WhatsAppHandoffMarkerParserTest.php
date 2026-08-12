<?php

namespace Tests\Feature;

use App\Services\OpaqueToken;
use App\Services\WhatsAppHandoffMarkerParser;
use Tests\TestCase;

class WhatsAppHandoffMarkerParserTest extends TestCase
{
    public function test_single_valid_marker_is_extracted_and_removed_from_message(): void
    {
        $token = app(OpaqueToken::class)->issue(OpaqueToken::HANDOFF_PREFIX);
        $result = app(WhatsAppHandoffMarkerParser::class)->extract("[SW:{$token}] jalan rusak");

        $this->assertSame($token, $result->token);
        $this->assertSame('jalan rusak', $result->message);
        $this->assertFalse($result->ambiguous);
    }

    public function test_missing_marker_preserves_direct_whatsapp_message(): void
    {
        $result = app(WhatsAppHandoffMarkerParser::class)->extract('jalan rusak');

        $this->assertNull($result->token);
        $this->assertSame('jalan rusak', $result->message);
        $this->assertFalse($result->ambiguous);
    }

    public function test_malformed_and_multiple_markers_are_removed_without_authority(): void
    {
        $first = app(OpaqueToken::class)->issue(OpaqueToken::HANDOFF_PREFIX);
        $second = app(OpaqueToken::class)->issue(OpaqueToken::HANDOFF_PREFIX);
        $malformed = app(WhatsAppHandoffMarkerParser::class)->extract('[SW:not-valid] jalan rusak');
        $multiple = app(WhatsAppHandoffMarkerParser::class)->extract(
            "[SW:{$first}] [SW:{$second}] jalan rusak",
        );

        $this->assertNull($malformed->token);
        $this->assertSame('jalan rusak', $malformed->message);
        $this->assertTrue($malformed->ambiguous);
        $this->assertNull($multiple->token);
        $this->assertSame('jalan rusak', $multiple->message);
        $this->assertTrue($multiple->ambiguous);
    }
}
