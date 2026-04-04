<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartAutoProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:start-auto-projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start all projects marked for auto-start';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Reset stale "running" statuses — PIDs that no longer exist after a reboot or crash
        Project::where('status', 'running')->each(function (Project $project) {
            $pid = (int) $project->pid;
            if (!$pid || !file_exists("/proc/{$pid}")) {
                $project->update(['status' => 'stopped', 'pid' => null]);
                Log::info("app:start-auto-projects: Reset stale running status for {$project->name} (PID {$pid} no longer exists)");
            }
        });

        $autoStartProjects = Project::where('auto_start', true)
            ->where('status', '!=', 'running')
            ->get();

        if ($autoStartProjects->isEmpty()) {
            $this->info('No projects to auto-start.');
            Log::info('app:start-auto-projects: No projects marked for auto-start');
            return 0;
        }

        $this->info("Found {$autoStartProjects->count()} project(s) to auto-start...");

        foreach ($autoStartProjects as $project) {
            try {
                $this->info("Starting {$project->name}...");

                // Resolve the project path
                $path = $project->local_path ?? $project->git_url;
                if (str_starts_with($path, '~')) {
                    $homeDir = posix_getpwuid(posix_getuid())['dir'];
                    $path = str_replace('~', $homeDir, $path);
                }

                // Check if path exists
                if (!is_dir($path)) {
                    $this->error("  ✗ Path does not exist: {$path}");
                    Log::error("app:start-auto-projects: Path not found for {$project->name}: {$path}");
                    continue;
                }

                // Spawn the project process
                $homeDir = posix_getpwuid(posix_getuid())['dir'];
                $command = "cd '{$path}' && env -i HOME='{$homeDir}' PATH='{$_SERVER['PATH']}' nohup php artisan serve --host={$project->ip_address} --port={$project->port} > /dev/null 2>&1 & echo $!";

                $output = [];
                $returnVar = 0;
                exec($command, $output, $returnVar);

                if ($returnVar === 0 && isset($output[0])) {
                    $pid = (int) $output[0];
                    $project->update(['status' => 'running', 'pid' => $pid]);
                    $this->info("  ✓ Started (PID: {$pid})");
                    Log::info("app:start-auto-projects: Started {$project->name} (PID: {$pid})");
                } else {
                    $this->error("  ✗ Failed to start");
                    Log::error("app:start-auto-projects: Failed to start {$project->name}");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Error: {$e->getMessage()}");
                Log::error("app:start-auto-projects: Exception starting {$project->name}: {$e->getMessage()}");
            }
        }

        $this->info('Auto-start completed.');
        return 0;
    }
}
