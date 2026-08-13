<?php

namespace App\Jobs;

use App\Services\WhatsAppMessageSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendWhatsAppText implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    /** @var list<int> */
    public array $backoff = [5, 30, 120, 300];

    public function __construct(
        public readonly string $recipient,
        public readonly string $message,
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsAppMessageSender $sender): void
    {
        $sender->sendTextOrFail($this->recipient, $this->message);
    }
}
