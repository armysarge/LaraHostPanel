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
    protected $description = 'Start all projects marked for auto-start, plus any that were running before the last reboot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ── 1. Reset stale "running" statuses (PIDs that died on reboot/crash)
        //        and remember their IDs so we can restart them below. ──────────
        $wasRunningIds = [];

        Project::where('status', 'running')->each(function (Project $project) use (&$wasRunningIds) {
            $pid = (int) $project->pid;
            if (!$pid || !file_exists("/proc/{$pid}")) {
                $wasRunningIds[] = $project->id;
                $project->update(['status' => 'stopped', 'pid' => null]);
                Log::info("app:start-auto-projects: Reset stale running status for {$project->name} (PID {$pid} no longer exists)");
            }
        });

        // ── 2. Collect projects to start:
        //        • explicitly flagged with auto_start = true, OR
        //        • were running just before this boot (stale PIDs above). ───────
        $toStart = Project::where(function ($q) use ($wasRunningIds) {
                $q->where('auto_start', true);
                if (!empty($wasRunningIds)) {
                    $q->orWhereIn('id', $wasRunningIds);
                }
            })
            ->where('status', '!=', 'running')
            ->get();

        if ($toStart->isEmpty()) {
            $this->info('No projects to auto-start.');
            Log::info('app:start-auto-projects: No projects to auto-start');
            return 0;
        }

        $this->info("Found {$toStart->count()} project(s) to auto-start...");

        foreach ($toStart as $project) {
            try {
                $this->info("Starting {$project->name}...");
                $this->startProject($project);
            } catch (\Exception $e) {
                $this->error("  ✗ Error: {$e->getMessage()}");
                Log::error("app:start-auto-projects: Exception starting {$project->name}: {$e->getMessage()}");
            }
        }

        $this->info('Auto-start completed.');
        return 0;
    }

    /**
     * Resolve the correct filesystem path and spawn the PHP server for a project.
     */
    private function startProject(Project $project): void
    {
        $homeDir = $_SERVER['HOME'] ?? '/tmp';
        $pathEnv = $_SERVER['PATH'] ?? '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
        $logFile = storage_path('logs/project-' . $project->id . '.log');

        // ── Resolve path based on source type ──────────────────────────────────
        if ($project->source_type === 'local') {
            $path = rtrim($project->local_path ?? '', '/');
            if (str_starts_with($path, '~/')) {
                $path = rtrim($homeDir, '/') . substr($path, 1);
            }

            if (!is_dir($path)) {
                $this->error("  ✗ Local path does not exist: {$path}");
                Log::error("app:start-auto-projects: Path not found for {$project->name}: {$path}");
                $project->update(['status' => 'error', 'pid' => null]);
                return;
            }
        } elseif ($project->source_type === 'git') {
            // Git projects are cloned into storage/app/deployments/{id}
            $path = storage_path('app/deployments/' . (int) $project->id);

            if (!is_dir($path)) {
                $this->error("  ✗ Git deployment path not found (not yet deployed?): {$path}");
                Log::error("app:start-auto-projects: Git deployment path not found for {$project->name}: {$path}");
                $project->update(['status' => 'error', 'pid' => null]);
                return;
            }
        } else {
            $this->error("  ✗ Unknown source_type '{$project->source_type}' — skipping");
            Log::error("app:start-auto-projects: Unknown source_type for {$project->name}: {$project->source_type}");
            return;
        }

        // ── Build the serve command (mirrors ProjectController::start logic) ───
        $ip   = filter_var($project->ip_address, FILTER_VALIDATE_IP) ?: $project->ip_address;
        $port = (int) $project->port;

        if (file_exists($path . '/artisan')) {
            $serve = "php artisan serve --host={$ip} --port={$port}";
        } elseif (is_dir($path . '/public')) {
            $serve = 'php -S ' . $ip . ':' . $port . ' -t ' . escapeshellarg($path . '/public');
        } else {
            $serve = 'php -S ' . $ip . ':' . $port;
        }

        // env -i gives the child a clean environment so its own .env loads
        // correctly without inheriting LaraHostPanel's DB_CONNECTION, APP_KEY, etc.
        $cmd = 'cd ' . escapeshellarg($path)
            . ' && nohup env -i'
            . ' HOME=' . escapeshellarg($homeDir)
            . ' PATH=' . escapeshellarg($pathEnv)
            . ' ' . $serve
            . ' > ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';

        $pid = (int) trim(exec($cmd));

        if ($pid > 0) {
            $project->update(['status' => 'running', 'pid' => $pid]);
            $this->info("  ✓ Started (PID: {$pid})");
            Log::info("app:start-auto-projects: Started {$project->name} (PID: {$pid})");
        } else {
            $project->update(['status' => 'error', 'pid' => null]);
            $this->error("  ✗ Failed to start — check {$logFile}");
            Log::error("app:start-auto-projects: Failed to start {$project->name} — check {$logFile}");
        }
    }
}
