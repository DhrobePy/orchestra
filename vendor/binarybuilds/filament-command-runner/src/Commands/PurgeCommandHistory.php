<?php

namespace BinaryBuilds\CommandRunner\Commands;

use BinaryBuilds\CommandRunner\Models\CommandRun;
use Illuminate\Console\Command;

class PurgeCommandHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command-runner:purge-history {days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purges command run history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Purging command runs older than ' . $this->argument('days') . ' days.');

        CommandRun::query()
            ->where('created_at', '<', now()->subDays($this->argument('days'))->toDateTimeString())
            ->delete();

        $this->info('Command runs purged successfully.');

        return Command::SUCCESS;
    }
}
