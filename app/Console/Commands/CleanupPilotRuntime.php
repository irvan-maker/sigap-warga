<?php

namespace App\Console\Commands;

use App\Models\ServiceHandoff;
use App\Models\WhatsAppConversation;
use Illuminate\Console\Command;

final class CleanupPilotRuntime extends Command
{
    protected $signature = 'pilot:cleanup-runtime {--pretend : Report eligible records without deleting them}';

    protected $description = 'Remove expired transient WhatsApp handoffs and conversations after the retention window';

    public function handle(): int
    {
        $cutoff = now()->subDays(7);
        $handoffs = ServiceHandoff::query()
            ->whereNull('consumed_at')
            ->where('expires_at', '<', $cutoff);
        $conversations = WhatsAppConversation::query()
            ->where('expires_at', '<', $cutoff);

        $handoffCount = (clone $handoffs)->count();
        $conversationCount = (clone $conversations)->count();

        if (! $this->option('pretend')) {
            $handoffs->delete();
            $conversations->delete();
        }

        $mode = $this->option('pretend') ? 'ditemukan' : 'dihapus';
        $this->info("{$handoffCount} handoff dan {$conversationCount} percakapan kedaluwarsa {$mode}.");

        return self::SUCCESS;
    }
}
