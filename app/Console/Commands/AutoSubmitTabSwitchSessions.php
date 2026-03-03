<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoSubmitTabSwitchSessions extends Command
{
    protected $signature = 'sessions:auto-submit-tab-switch';

    protected $description = 'Legacy auto-submit command (no-op)';

    public function handle(): int
    {
        $this->info('Legacy auto-submit disabled; no action taken.');
        return Command::SUCCESS;
    }
}
