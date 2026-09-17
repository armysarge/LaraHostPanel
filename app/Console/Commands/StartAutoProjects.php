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
        // ── 1. Reset stale "running" statuses (PIDs that died on reboot/crash,
        //        or were recycled by an unrelated process) and remember their
        //        IDs so we can restart them below. ──────────────────────────────
        $wasRunningIds = [];

        Project::where('status', 'running')->each(function (Project $project) use (&$wasRunningIds) {
            if (!$project->hasLiveProcess()) {
                $pid = (int) $project->pid;
                $wasRunningIds[] = $project->id;
                $project->update(['status' => 'stopped', 'pid' => null]);
                Log::info("app:start-auto-projects: Reset stale running status for {$project->name} (PID {$pid} is no longer this project's server)");
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

        $serve = $project->buildServeCommand($path);

        // env -i gives the child a clean environment so its own .env loads
        // correctly without inheriting LaraHostPanel's DB_CONNECTION, APP_KEY, etc.
        $cmd = 'cd ' . escapeshellarg($path)
            . ' && nohup env -i'
            . ' HOME=' . escapeshellarg($homeDir)
            . ' PATH=' . escapeshellarg($pathEnv)
            . ' ' . $serve
            . ' < /dev/null > ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';

        $pid = $this->runLauncher($cmd);

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

    /**
     * Run a `cmd & echo $!`-style launcher and return the backgrounded PID.
     *
     * We've seen this launcher shell occasionally wedge indefinitely instead
     * of exiting right after backgrounding the job (observed with Octane's
     * roadrunner server; root cause unconfirmed). A plain exec() would block
     * forever reading the shell's stdout pipe in that case, which stalls
     * every later auto-start project and the panel's own `php artisan serve`.
     * proc_open() gives us the exact launcher PID so we can forcibly kill
     * *it* (not its already-detached, already-redirected grandchild) once a
     * generous deadline passes, without depending on the external `timeout`
     * binary's own child-tracking.
     */
    private function runLauncher(string $cmd, int $timeoutSeconds = 10): int
    {
        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(['sh', '-c', $cmd], $descriptors, $pipes);

        if (!is_resource($process)) {
            return 0;
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $out = '';
        $deadline = microtime(true) + $timeoutSeconds;
        $status = proc_get_status($process);

        while ($status['running'] && microtime(true) < $deadline) {
            $out .= stream_get_contents($pipes[1]);
            usleep(100_000);
            $status = proc_get_status($process);
        }

        if ($status['running']) {
            Log::warning("app:start-auto-projects: Launcher did not exit within {$timeoutSeconds}s; killing it (the service itself may already be up — check its log).");
            proc_terminate($process, SIGKILL);
            usleep(200_000);
        }

        $out .= stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return (int) trim($out);
    }
}
