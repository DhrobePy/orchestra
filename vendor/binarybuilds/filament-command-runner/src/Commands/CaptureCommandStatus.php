<?php

namespace BinaryBuilds\CommandRunner\Commands;

use BinaryBuilds\CommandRunner\Models\CommandRun;
use Illuminate\Console\Command;

class CaptureCommandStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command-runner:capture-status {id} {code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Captures status and output of command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $command = CommandRun::find($this->argument('id'));

        if ($command) {
            $command->completed_at = now()->toDateTimeString();
            $command->exit_code = $this->argument('code');

            $logFile = storage_path('command-runs/' . $command->id . '.log');

            if (file_exists($logFile)) {
                $command->output = trim(file_get_contents($logFile));
                unlink($logFile);
            }

            $command->save();
        }

        return Command::SUCCESS;
    }
}
