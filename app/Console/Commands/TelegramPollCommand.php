<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramBotAssistantService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--loop : Keep polling continuously in a loop}';
    protected $description = 'Poll incoming messages and interactive button clicks from Telegram bot';

    public function handle(TelegramBotAssistantService $assistant): int
    {
        $this->info('🤖 Starting Telegram Bot Assistant listener...');

        $keepLooping = (bool) $this->option('loop');

        do {
            try {
                $result = $assistant->pollUpdates();

                if ($result['success'] && ($result['processed_count'] ?? 0) > 0) {
                    $this->line("<info>[✓]</info> Processed {$result['processed_count']} incoming update(s) at " . date('H:i:s'));
                }
            } catch (\Throwable $e) {
                $this->error("Poll error: " . $e->getMessage());
            }

            if ($keepLooping) {
                sleep(2);
            }
        } while ($keepLooping);

        $this->info('Poll completed successfully.');
        return Command::SUCCESS;
    }
}
