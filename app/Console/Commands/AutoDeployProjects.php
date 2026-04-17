<?php

namespace App\Console\Commands;

use App\Actions\DeployGitProject;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoDeployProjects extends Command
{
    protected $signature = 'app:auto-deploy-projects';

    protected $description = 'Check git projects for remote updates and re-deploy if the remote has new commits';

    public function handle(DeployGitProject $deployer): int
    {
        $projects = Project::where('auto_deploy', true)
            ->where('source_type', 'git')
            ->get();

        if ($projects->isEmpty()) {
            return 0;
        }

        foreach ($projects as $project) {
            $intervalMinutes = max(1, (int) $project->auto_deploy_interval);
            $lastChecked     = $project->last_checked_at ?? $project->created_at;

            // Skip until the configured interval has elapsed since the last check
            if (now()->lt($lastChecked->copy()->addMinutes($intervalMinutes))) {
                continue;
            }

            $this->line("Checking <info>{$project->name}</info>...");

            try {
                // Record that we checked, regardless of whether we deploy
                $project->update(['last_checked_at' => now()]);

                if (!$deployer->hasRemoteChanges($project)) {
                    Log::info("[AutoDeploy] {$project->name} (#{$project->id}): no remote changes detected.");
                    $this->line("  → No changes.");
                    continue;
                }

                $this->line("  → Remote changes detected, re-deploying...");
                Log::info("[AutoDeploy] {$project->name} (#{$project->id}): remote changes detected, deploying.");

                // Kill any running process before pulling
                if ($project->pid) {
                    $pid = (int) $project->pid;
                    exec("kill -TERM {$pid} 2>/dev/null");
                    exec("pkill -TERM -P {$pid} 2>/dev/null");
                    $project->update(['status' => 'deploying', 'pid' => null]);
                }

                $success = $deployer->execute($project);

                if ($success) {
                    $this->info("  ✓ Re-deployed successfully.");
                } else {
                    $this->error("  ✗ Re-deploy failed. Check deployment logs.");
                }
            } catch (\Throwable $e) {
                Log::error("[AutoDeploy] {$project->name} (#{$project->id}): exception — {$e->getMessage()}");
                $this->error("  ✗ Exception: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
