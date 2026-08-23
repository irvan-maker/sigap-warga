<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWhatsAppInboundEvent;
use App\Services\WhatsAppWebhookParser;
use App\Services\WhatsAppWebhookSignatureVerifier;
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
                dispatch(ProcessWhatsAppInboundEvent::fromEvent($event));
            }
        } catch (Throwable $throwable) {
            report($throwable);

            return response('', 500);
        }

        return response('EVENT_RECEIVED', 200)->header('Content-Type', 'text/plain');
    }

    private function metaQuery(Request $request, string $name): ?string
    {
        $values = [];

        foreach (explode('&', (string) $request->server('QUERY_STRING', '')) as $parameter) {
            if ($parameter === '') {
                continue;
            }

            [$rawKey, $rawValue] = array_pad(explode('=', $parameter, 2), 2, '');

            if (urldecode($rawKey) === $name) {
                $values[] = urldecode($rawValue);
            }
        }

        if (count($values) > 1) {
            return null;
        }

        if ($values !== []) {
            return $values[0];
        }

        $value = $request->query(str_replace('.', '_', $name));

        return is_string($value) ? $value : null;
    }
}
