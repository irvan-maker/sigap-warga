<?php

namespace App\Http\Controllers;

use App\Services\ProcessTrustedInboundEvent;
use App\Services\WhatsAppWebhookParser;
use App\Services\WhatsAppWebhookSignatureVerifier;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonException;
use Throwable;

final class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $this->metaQuery($request, 'hub.mode');
        $providedToken = $this->metaQuery($request, 'hub.verify_token');
        $challenge = $this->metaQuery($request, 'hub.challenge');
        $configuredToken = config('services.whatsapp.webhook_verify_token');

        if ($mode !== 'subscribe'
            || ! is_string($configuredToken)
            || $configuredToken === ''
            || $providedToken === null
            || ! hash_equals($configuredToken, $providedToken)
            || $challenge === null) {
            return response('', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(
        Request $request,
        WhatsAppWebhookSignatureVerifier $signatureVerifier,
        WhatsAppWebhookParser $parser,
        ProcessTrustedInboundEvent $processor,
    ): Response {
        $rawBody = $request->getContent();

        if (! $signatureVerifier->verify(
            $rawBody,
            $request->header('X-Hub-Signature-256'),
        )) {
            return response('', 403);
        }

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response('', 400);
        }

        if (! is_array($payload)) {
            return response('', 400);
        }

        try {
            $result = $parser->parse($payload);

            foreach ($result->events as $event) {
                try {
                    $processor->process($event);
                } catch (DomainException) {
                    // A signed but invalid message is acknowledged without trust leakage.
                }
            }
        } catch (Throwable) {
            return response('', 500);
        }

        return response('EVENT_RECEIVED', 200)->header('Content-Type', 'text/plain');
    }

    private function metaQuery(Request $request, string $name): ?string
    {
        $value = $request->query($name) ?? $request->query(str_replace('.', '_', $name));

        return is_string($value) ? $value : null;
    }
}
