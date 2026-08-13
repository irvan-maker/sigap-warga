<?php

namespace Tests\Feature;

use App\Enums\InboundProcessingReason;
use App\Enums\InboundRequestStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportPriority;
use App\Enums\ServiceRouteTarget;
use App\Jobs\ProcessWhatsAppInboundEvent;
use App\Models\Citizen;
use App\Models\InboundRequest;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\ServiceHandoff;
use App\Models\WhatsAppConversation;
use App\Services\ServiceEntryPointIssuer;
use App\Services\ServiceHandoffIssuer;
use App\Services\WhatsAppWebhookParser;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const APP_SECRET = 'test-meta-app-secret';

    private const VERIFY_TOKEN = 'test-webhook-verification-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.app_secret' => self::APP_SECRET,
            'services.whatsapp.webhook_verify_token' => self::VERIFY_TOKEN,
            'services.whatsapp.source_namespace' => 'meta-whatsapp-test',
        ]);
    }

    public function test_valid_qr_handoff_creates_same_territory_report_once_with_clean_message(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $handoffToken = $this->handoffToken($rt);
        $rawBody = $this->textPayload(
            'wamid.qr-golden',
            $citizen->phone_normalized,
            "[SW:{$handoffToken}] jalan rusak",
        );

        $this->postRaw($rawBody, $this->signature($rawBody))->assertOk();
        $this->postRaw($rawBody, $this->signature($rawBody))->assertOk();

        $inbound = InboundRequest::query()->firstOrFail();
        $report = $inbound->report()->firstOrFail();
        $handoff = ServiceHandoff::query()->firstOrFail();

        $this->assertSame(InboundRequestStatus::SUCCEEDED, $inbound->status);
        $this->assertSame(ServiceRouteTarget::REPORT_SERVICE, $inbound->service_target);
        $this->assertSame($inbound->id, $handoff->consumed_by_inbound_request_id);
        $this->assertSame($rt->id, $report->rt_id);
        $this->assertSame($rt->id, $citizen->fresh()->rt_id);
        $this->assertSame('jalan rusak', $report->description);
        $this->assertStringNotContainsString('[SW:', $report->description);
        $this->assertDatabaseCount('inbound_requests', 1);
        $this->assertDatabaseCount('reports', 1);
        $this->assertDatabaseCount('report_histories', 1);
        $this->assertDatabaseCount('report_ticket_sequences', 1);
    }

    public function test_qr_start_message_keeps_private_conversation_context_for_the_next_report(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $this->postSignedText('wamid.qr-start', $citizen->phone_normalized, $this->entryMessage($rt))
            ->assertOk();
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('whatsapp_conversations', 1);

        $this->postSignedText('wamid.qr-follow-up', $citizen->phone_normalized, 'jalan rusak di depan balai warga')
            ->assertOk();

        $report = $citizen->reports()->sole();
        $conversation = WhatsAppConversation::query()->sole();
        $this->assertSame($rt->id, $report->entry_rt_id);
        $this->assertSame($rt->id, $report->incident_rt_id);
        $this->assertSame($rt->id, $report->current_rt_id);
        $this->assertSame(ReportCategory::ROAD_DAMAGE, $report->category);
        $this->assertSame(ReportPriority::NORMAL, $report->priority);
        $this->assertNotSame($citizen->phone_normalized, $conversation->participant_hash);
        $this->assertNotContains($citizen->phone_normalized, $conversation->getAttributes(), true);
    }

    public function test_qr_start_sends_welcome_reply_through_configured_meta_endpoint(): void
    {
        Http::fake([
            'https://graph.facebook.com/v23.0/phone-number-001/messages' => Http::response([
                'messages' => [['id' => 'wamid.outbound-welcome']],
            ]),
        ]);
        config([
            'services.whatsapp.outbound_enabled' => true,
            'services.whatsapp.access_token' => 'test-access-token',
            'services.whatsapp.phone_number_id' => 'phone-number-001',
            'services.whatsapp.graph_version' => 'v23.0',
        ]);
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $this->postSignedText('wamid.qr-welcome', $citizen->phone_normalized, $this->entryMessage($rt))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://graph.facebook.com/v23.0/phone-number-001/messages'
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && $request['to'] === $citizen->phone_normalized
            && str_contains($request['text']['body'], 'Apa yang bisa saya bantu?')
        );
    }

    public function test_start_typed_without_an_active_qr_context_is_not_claimed_as_an_official_scan(): void
    {
        Http::fake([
            'https://graph.facebook.com/v23.0/phone-number-001/messages' => Http::response([
                'messages' => [['id' => 'wamid.outbound-no-qr-context']],
            ]),
        ]);
        config([
            'services.whatsapp.outbound_enabled' => true,
            'services.whatsapp.access_token' => 'test-access-token',
            'services.whatsapp.phone_number_id' => 'phone-number-001',
            'services.whatsapp.graph_version' => 'v23.0',
        ]);
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);

        $this->postSignedText(
            'wamid.start-without-qr',
            $citizen->phone_normalized,
            'MULAI LAPORAN SIGAP WARGA',
        )->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains(
            $request['text']['body'],
            'Konteks QR belum dapat diverifikasi',
        ));
        $this->assertDatabaseCount('whatsapp_conversations', 0);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_qr_entry_conflicting_with_domicile_is_accepted_at_domicile_without_changing_identity(): void
    {
        $domicileRt = $this->createRt();
        $otherRw = Rw::query()->create(['code' => '007', 'name' => 'RW 007']);
        $entryRt = Rt::query()->create([
            'rw_id' => $otherRw->id,
            'code' => '007',
            'name' => 'RT 007',
        ]);
        $citizen = Citizen::factory()->for($domicileRt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $this->postSignedText(
            'wamid.qr-context-before-conflict',
            $citizen->phone_normalized,
            $this->entryMessage($domicileRt),
        )->assertOk();
        $this->postSignedText(
            'wamid.qr-conflict',
            $citizen->phone_normalized,
            $this->entryMessage($entryRt, 'jalan rusak'),
        )
            ->assertOk();

        $this->assertSame(
            InboundRequestStatus::SUCCEEDED,
            InboundRequest::query()->where('external_event_id', 'wamid.qr-conflict')->sole()->status,
        );
        $this->assertSame($domicileRt->id, $citizen->fresh()->rt_id);
        $report = Report::query()->sole();
        $this->assertSame($domicileRt->id, $report->rt_id);
        $this->assertSame($entryRt->id, $report->entry_rt_id);
    }

    public function test_qr_handoff_does_not_authenticate_unknown_citizen(): void
    {
        $rt = $this->createRt();

        $this->postSignedText(
            'wamid.qr-unknown',
            '6289999999999',
            $this->entryMessage($rt, 'jalan rusak'),
        )
            ->assertOk();

        $this->assertSame(InboundRequestStatus::BLOCKED, InboundRequest::query()->sole()->status);
        $this->assertSame(InboundProcessingReason::IDENTITY_REQUIRED, InboundRequest::query()->sole()->processing_reason);
        $this->assertDatabaseCount('citizens', 0);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_handoff_replay_by_different_meta_message_is_rejected(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $token = $this->handoffToken($rt);

        $this->postSignedText('wamid.qr-first', $citizen->phone_normalized, "[SW:{$token}] jalan rusak")->assertOk();
        $this->postSignedText('wamid.qr-replay', $citizen->phone_normalized, "[SW:{$token}] jalan rusak")->assertOk();

        $this->assertDatabaseCount('inbound_requests', 2);
        $this->assertDatabaseCount('reports', 1);
        $this->assertSame(
            InboundRequestStatus::BLOCKED,
            InboundRequest::query()->where('external_event_id', 'wamid.qr-replay')->sole()->status,
        );
    }

    public function test_malformed_and_multiple_markers_never_provide_entry_authority(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $entry = app(ServiceEntryPointIssuer::class)->issue($rt);
        $first = app(ServiceHandoffIssuer::class)->issue($entry->record)->token;
        $second = app(ServiceHandoffIssuer::class)->issue($entry->record)->token;

        $this->postSignedText('wamid.qr-malformed', $citizen->phone_normalized, '[SW:not-valid] jalan rusak')->assertOk();
        $this->postSignedText('wamid.qr-multiple', $citizen->phone_normalized, "[SW:{$first}] [SW:{$second}] jalan rusak")->assertOk();

        $this->assertDatabaseCount('reports', 0);
        $this->assertSame(2, InboundRequest::query()->where('status', InboundRequestStatus::BLOCKED)->count());
        $this->assertSame(0, ServiceHandoff::query()->whereNotNull('consumed_at')->count());
    }

    public function test_get_verification_returns_challenge_for_matching_subscription(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token='.
            self::VERIFY_TOKEN.'&hub.challenge=challenge-123')
            ->assertOk()
            ->assertSeeText('challenge-123');
    }

    public function test_get_verification_accepts_real_meta_query_with_conflicting_underscore_aliases(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe'.
            '&hub.challenge=1401067747'.
            '&hub.verify_token='.self::VERIFY_TOKEN.
            '&hub_mode=subscribe'.
            '&hub_challenge=1401067747'.
            '&hub_verify_token=DIFFERENT_TOKEN')
            ->assertOk()
            ->assertContent('1401067747');
    }

    public function test_get_verification_does_not_allow_underscore_token_to_override_wrong_dotted_token(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe'.
            '&hub.verify_token=WRONG_TOKEN'.
            '&hub.challenge=challenge-123'.
            '&hub_verify_token='.self::VERIFY_TOKEN)
            ->assertForbidden();
    }

    public function test_get_verification_rejects_wrong_token_without_leaking_secret(): void
    {
        $response = $this->get(
            '/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=challenge-123',
        );

        $response->assertForbidden();
        $response->assertDontSee(self::VERIFY_TOKEN);
        $response->assertDontSee(self::APP_SECRET);
    }

    public function test_get_verification_supports_underscore_aliases_when_dotted_parameters_are_absent(): void
    {
        $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token='.
            self::VERIFY_TOKEN.'&hub_challenge=compatibility-challenge')
            ->assertOk()
            ->assertContent('compatibility-challenge');
    }

    public function test_get_verification_rejects_missing_challenge(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token='.self::VERIFY_TOKEN)
            ->assertForbidden();
    }

    public function test_get_verification_rejects_duplicate_exact_verify_token(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe'.
            '&hub.verify_token='.self::VERIFY_TOKEN.
            '&hub.verify_token=DIFFERENT_TOKEN'.
            '&hub.challenge=challenge-123')
            ->assertForbidden();
    }

    public function test_get_verification_rejects_wrong_or_missing_mode(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=unsubscribe&hub.verify_token='.
            self::VERIFY_TOKEN.'&hub.challenge=challenge-123')
            ->assertForbidden();

        $this->get('/webhooks/whatsapp?hub.verify_token='.
            self::VERIFY_TOKEN.'&hub.challenge=challenge-123')
            ->assertForbidden();
    }

    public function test_valid_raw_body_hmac_is_accepted(): void
    {
        $rawBody = $this->textPayload('wamid.valid-signature', '6289999999999', 'nomor ambulans desa berapa');

        $this->postSigned($rawBody)->assertOk()->assertSeeText('EVENT_RECEIVED');

        $this->assertDatabaseCount('inbound_requests', 1);
    }

    public function test_verified_webhook_dispatches_an_encrypted_whatsapp_queue_job(): void
    {
        Queue::fake();
        $rawBody = $this->textPayload('wamid.queued', '6289999999999', 'jalan rusak');

        $this->postSigned($rawBody)->assertOk()->assertSeeText('EVENT_RECEIVED');

        Queue::assertPushed(ProcessWhatsAppInboundEvent::class, function ($job): bool {
            $this->assertInstanceOf(ShouldBeEncrypted::class, $job);

            return $job->queue === 'whatsapp';
        });
        $this->assertDatabaseCount('inbound_requests', 0);
    }

    public function test_invalid_missing_and_malformed_signatures_are_rejected(): void
    {
        $rawBody = $this->textPayload('wamid.signature-rejected', '6289999999999', 'jalan rusak');

        $invalid = $this->postRaw($rawBody, 'sha256='.str_repeat('0', 64));
        $invalid->assertForbidden();
        $invalid->assertDontSee(self::APP_SECRET);
        $invalid->assertDontSee(self::VERIFY_TOKEN);
        $this->postRaw($rawBody, null)->assertForbidden();
        $this->postRaw($rawBody, 'sha256=not-hex')->assertForbidden();

        $this->assertDatabaseCount('inbound_requests', 0);
    }

    public function test_body_changed_after_signing_is_rejected(): void
    {
        $original = $this->textPayload('wamid.original', '6289999999999', 'jalan rusak');
        $changed = $this->textPayload('wamid.changed', '6289999999999', 'jalan rusak');

        $this->postRaw($changed, $this->signature($original))->assertForbidden();

        $this->assertDatabaseCount('inbound_requests', 0);
    }

    public function test_parser_preserves_signed_sender_provider_id_and_account_scoped_source(): void
    {
        $payload = json_decode(
            $this->textPayload('wamid.identity-001', '6281234567890', 'jalan rusak'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $result = app(WhatsAppWebhookParser::class)->parse($payload);

        $this->assertCount(1, $result->events);
        $this->assertSame('wamid.identity-001', $result->events[0]->externalEventId);
        $this->assertSame('6281234567890', $result->events[0]->senderPhone);
        $this->assertSame('meta-whatsapp-test:123456789012345', $result->events[0]->durableSourceNamespace());
        $this->assertNull($result->events[0]->entryRt);
        $this->assertNull($result->events[0]->incidentRt);
    }

    public function test_parser_extracts_multiple_text_messages_deterministically(): void
    {
        $payload = json_decode($this->payload([
            'messages' => [
                [
                    'from' => '6281111111111',
                    'id' => 'wamid.batch-001',
                    'timestamp' => '1786442400',
                    'text' => ['body' => 'jalan rusak'],
                    'type' => 'text',
                ],
                [
                    'from' => '6282222222222',
                    'id' => 'wamid.batch-002',
                    'timestamp' => '1786442401',
                    'text' => ['body' => 'nomor ambulans berapa'],
                    'type' => 'text',
                ],
            ],
        ]), true, 512, JSON_THROW_ON_ERROR);

        $result = app(WhatsAppWebhookParser::class)->parse($payload);

        $this->assertSame(
            ['wamid.batch-001', 'wamid.batch-002'],
            array_map(fn ($event): string => $event->externalEventId, $result->events),
        );
    }

    public function test_known_citizen_report_without_whatsapp_territory_is_durably_blocked(): void
    {
        $rt = $this->createRt();
        Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $rawBody = $this->textPayload('wamid.report-blocked', '6281234567890', 'jalan rusak');

        $this->postSigned($rawBody)->assertOk();

        $inbound = InboundRequest::query()->sole();
        $this->assertSame(InboundRequestStatus::BLOCKED, $inbound->status);
        $this->assertSame(ServiceRouteTarget::REPORT_SERVICE, $inbound->service_target);
        $this->assertSame(InboundProcessingReason::TERRITORY_REQUIRED, $inbound->processing_reason);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_duplicate_delivery_reuses_receipt_correlation_and_does_not_reprocess(): void
    {
        $rawBody = $this->textPayload('wamid.duplicate-001', '6289999999999', 'jalan rusak');

        $this->postSigned($rawBody)->assertOk();
        $first = InboundRequest::query()->sole();
        $this->postSigned($rawBody)->assertOk();
        $duplicate = InboundRequest::query()->sole();

        $this->assertSame($first->id, $duplicate->id);
        $this->assertSame($first->correlation_id, $duplicate->correlation_id);
        $this->assertSame(1, $duplicate->attempt_count);
        $this->assertDatabaseCount('inbound_requests', 1);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_status_callback_and_media_message_are_acknowledged_without_receipts(): void
    {
        $statusBody = $this->payload([
            'statuses' => [[
                'id' => 'wamid.outbound-status',
                'status' => 'delivered',
                'timestamp' => '1786442400',
            ]],
        ]);
        $imageBody = $this->payload([
            'messages' => [[
                'from' => '6281234567890',
                'id' => 'wamid.image-001',
                'timestamp' => '1786442400',
                'type' => 'image',
                'image' => ['id' => 'media-id'],
            ]],
        ]);

        $this->postSigned($statusBody)->assertOk();
        $this->postSigned($imageBody)->assertOk();

        $this->assertDatabaseCount('inbound_requests', 0);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_malformed_json_with_valid_signature_fails_safely(): void
    {
        $response = $this->postSigned('{"object":');

        $response->assertBadRequest();
        $response->assertDontSee(self::APP_SECRET);
        $this->assertDatabaseCount('inbound_requests', 0);
    }

    public function test_public_information_waits_for_service_without_report(): void
    {
        $rawBody = $this->textPayload(
            'wamid.public-information',
            '6289999999999',
            'nomor ambulans desa berapa',
        );

        $this->postSigned($rawBody)->assertOk();

        $inbound = InboundRequest::query()->sole();
        $this->assertSame(InboundRequestStatus::PENDING_ACTION, $inbound->status);
        $this->assertSame(ServiceRouteTarget::INFORMATION_SERVICE, $inbound->service_target);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_emergency_without_trusted_territory_is_blocked_without_dispatch_or_report(): void
    {
        $rawBody = $this->textPayload(
            'wamid.emergency',
            '6289999999999',
            'tolong ambulans, ada orang pingsan',
        );

        $this->postSigned($rawBody)->assertOk();

        $inbound = InboundRequest::query()->sole();
        $this->assertSame(InboundRequestStatus::BLOCKED, $inbound->status);
        $this->assertSame(ServiceRouteTarget::EMERGENCY_SERVICE, $inbound->service_target);
        $this->assertSame(InboundProcessingReason::TERRITORY_REQUIRED, $inbound->processing_reason);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_receipt_does_not_persist_phone_message_payload_or_signature(): void
    {
        $rawBody = $this->textPayload(
            'wamid.private',
            '6289999999999',
            'nomor ambulans desa berapa',
        );

        $this->postSigned($rawBody)->assertOk();

        $attributes = InboundRequest::query()->sole()->getAttributes();
        $this->assertNotContains('6289999999999', $attributes, true);
        $this->assertNotContains('nomor ambulans desa berapa', $attributes, true);
        $this->assertNotContains($rawBody, $attributes, true);
        $this->assertNotContains($this->signature($rawBody), $attributes, true);
    }

    public function test_csrf_exemption_is_scoped_to_whatsapp_webhook(): void
    {
        $rawBody = $this->textPayload('wamid.csrf', '6289999999999', 'jalan rusak');

        $this->postRaw($rawBody, null)->assertForbidden();

        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertIsString($bootstrap);
        $this->assertMatchesRegularExpression(
            "/validateCsrfTokens\(except: \[\s*'webhooks\/whatsapp',\s*\]\)/",
            $bootstrap,
        );
        $this->assertStringNotContainsString("validateCsrfTokens(except: ['*'])", $bootstrap);
    }

    private function postSigned(string $rawBody): TestResponse
    {
        return $this->postRaw($rawBody, $this->signature($rawBody));
    }

    private function postRaw(string $rawBody, ?string $signature): TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($signature !== null) {
            $server['HTTP_X_HUB_SIGNATURE_256'] = $signature;
        }

        return $this->call(
            'POST',
            '/webhooks/whatsapp',
            [],
            [],
            [],
            $server,
            $rawBody,
        );
    }

    private function signature(string $rawBody): string
    {
        return 'sha256='.hash_hmac('sha256', $rawBody, self::APP_SECRET);
    }

    private function postSignedText(string $messageId, string $sender, string $message): TestResponse
    {
        $rawBody = $this->textPayload($messageId, $sender, $message);

        return $this->postRaw($rawBody, $this->signature($rawBody));
    }

    private function handoffToken(Rt $rt): string
    {
        $entry = app(ServiceEntryPointIssuer::class)->issue($rt);

        return app(ServiceHandoffIssuer::class)->issue($entry->record)->token;
    }

    private function entryMessage(Rt $rt, string $message = 'MULAI LAPORAN SIGAP WARGA'): string
    {
        app(ServiceEntryPointIssuer::class)->issue($rt);

        return "{$message}\n\nPintu layanan:\n{$rt->code} / {$rt->rw->code}";
    }

    private function textPayload(string $messageId, string $sender, string $message): string
    {
        return $this->payload([
            'contacts' => [[
                'profile' => ['name' => 'Warga'],
                'wa_id' => $sender,
            ]],
            'messages' => [[
                'from' => $sender,
                'id' => $messageId,
                'timestamp' => '1786442400',
                'text' => ['body' => $message],
                'type' => 'text',
            ]],
        ]);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function payload(array $value): string
    {
        return json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'waba-001',
                'changes' => [[
                    'value' => array_merge([
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '15550000000',
                            'phone_number_id' => '123456789012345',
                        ],
                    ], $value),
                    'field' => 'messages',
                ]],
            ]],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
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
