<?php

namespace App\Actions;

use App\Models\Project;
use Illuminate\Support\Facades\Log;

class DeployGitProject
{
    /**
     * Clone (or pull) a git project and start its PHP server.
     * Returns true on success, false on failure.
     */
    public function execute(Project $project): bool
    {
        $startedAt  = now();
        $deployPath = storage_path('app/deployments/' . (int) $project->id);
        $rawBranch  = $project->branch ?? 'main';

        ['gitUrl' => $gitUrl, 'gitEnvPrefix' => $gitEnvPrefix, 'sshKeyFile' => $sshKeyFile] =
            $this->buildCredentials($project);

        $cmdOutput = [];
        $exitCode  = 0;

        try {
            if (is_dir($deployPath . '/.git')) {
                // Pull latest commits on the target branch
                $cmd = 'cd ' . escapeshellarg($deployPath)
                    . ' && ' . $gitEnvPrefix . 'git fetch origin 2>&1'
                    . ' && ' . $gitEnvPrefix . 'git checkout ' . escapeshellarg($rawBranch) . ' 2>&1'
                    . ' && ' . $gitEnvPrefix . 'git reset --hard ' . escapeshellarg('origin/' . $rawBranch) . ' 2>&1';
                exec($cmd, $cmdOutput, $exitCode);
            } else {
                // Remove any partial/failed clone before starting fresh.
                $expectedBase = storage_path('app/deployments');
                if (is_dir($deployPath) && str_starts_with(realpath($deployPath) ?: $deployPath, $expectedBase)) {
                    exec('rm -rf ' . escapeshellarg($deployPath));
                }
                @mkdir($deployPath, 0755, true);

                $cmd = $gitEnvPrefix
                    . 'git clone --branch ' . escapeshellarg($rawBranch)
                    . ' -- ' . escapeshellarg($gitUrl)
                    . ' ' . escapeshellarg($deployPath) . ' 2>&1';
                exec($cmd, $cmdOutput, $exitCode);
            }
        } finally {
            if ($sshKeyFile && file_exists($sshKeyFile)) {
                unlink($sshKeyFile);
            }
        }

        $gitOutput = implode("\n", $cmdOutput);

        if ($exitCode !== 0) {
            $project->update(['status' => 'error', 'pid' => null]);
            Log::error("[LaraHostPanel] {$project->name} (#{$project->id}): git operation failed (exit {$exitCode}).");
            $project->deploymentLogs()->create([
                'status'       => 'failed',
                'output'       => "Git operation failed (exit {$exitCode}):\n{$gitOutput}",
                'started_at'   => $startedAt,
                'completed_at' => now(),
            ]);
            return false;
        }

        // Resolve commit hash
        $hashLines = [];
        exec('cd ' . escapeshellarg($deployPath) . ' && git rev-parse HEAD 2>/dev/null', $hashLines);
        $commitHash = trim($hashLines[0] ?? '');

        // Start the PHP server
        $ip      = filter_var($project->ip_address, FILTER_VALIDATE_IP) ?: $project->ip_address;
        $port    = (int) $project->port;
        $logFile = storage_path('logs/project-' . $project->id . '.log');

        if (file_exists($deployPath . '/artisan')) {
            $serve = "php artisan serve --host={$ip} --port={$port}";
        } elseif (is_dir($deployPath . '/public')) {
            $serve = 'php -S ' . $ip . ':' . $port . ' -t ' . escapeshellarg($deployPath . '/public');
        } else {
            $serve = 'php -S ' . $ip . ':' . $port;
        }

        $startCmd = 'cd ' . escapeshellarg($deployPath)
            . ' && nohup env -i'
            . ' HOME=' . escapeshellarg($_SERVER['HOME'] ?? (posix_getpwuid(posix_getuid())['dir'] ?? '/tmp'))
            . ' PATH=' . escapeshellarg($_SERVER['PATH'] ?? '/usr/local/bin:/usr/bin:/bin')
            . ' ' . $serve
            . ' > ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';

        $pid = (int) exec($startCmd);

        if ($pid > 0) {
            $project->update([
                'status'           => 'running',
                'pid'              => $pid,
                'last_deployed_at' => now(),
                'last_commit_hash' => $commitHash ?: null,
            ]);
            Log::info("[LaraHostPanel] {$project->name} (#{$project->id}) deployed from git, started with PID {$pid}.");
            $project->deploymentLogs()->create([
                'status'       => 'success',
                'commit_hash'  => $commitHash ?: null,
                'output'       => $gitOutput . "\n\nStarted with PID {$pid}.\nServing: {$serve}",
                'started_at'   => $startedAt,
                'completed_at' => now(),
            ]);
            return true;
        }

        $project->update(['status' => 'error', 'pid' => null]);
        Log::error("[LaraHostPanel] {$project->name} (#{$project->id}): server process did not start after git deploy.");
        $project->deploymentLogs()->create([
            'status'       => 'failed',
            'commit_hash'  => $commitHash ?: null,
            'output'       => $gitOutput . "\n\nGit pull succeeded but PHP server failed to start.",
            'started_at'   => $startedAt,
            'completed_at' => now(),
        ]);
        return false;
    }

    /**
     * Fetch from origin and compare local HEAD to the remote branch tip.
     * Returns true if the remote has new commits (or the repo is not yet cloned).
     */
    public function hasRemoteChanges(Project $project): bool
    {
        $deployPath = storage_path('app/deployments/' . (int) $project->id);

        if (!is_dir($deployPath . '/.git')) {
            return true; // not cloned yet — treat as needing deployment
        }

        ['gitEnvPrefix' => $gitEnvPrefix, 'sshKeyFile' => $sshKeyFile] =
            $this->buildCredentials($project);

        try {
            $fetchOutput = [];
            $exitCode    = 0;
            exec(
                'cd ' . escapeshellarg($deployPath) . ' && ' . $gitEnvPrefix . 'git fetch origin 2>&1',
                $fetchOutput,
                $exitCode
            );

            if ($exitCode !== 0) {
                Log::warning("[LaraHostPanel] {$project->name} (#{$project->id}): git fetch failed during change check.");
                return false;
            }

            $branchRef   = 'origin/' . ($project->branch ?? 'main');
            $localLines  = [];
            $remoteLines = [];
            exec('cd ' . escapeshellarg($deployPath) . ' && git rev-parse HEAD 2>/dev/null', $localLines);
            exec('cd ' . escapeshellarg($deployPath) . ' && git rev-parse ' . escapeshellarg($branchRef) . ' 2>/dev/null', $remoteLines);

            $local  = trim($localLines[0] ?? '');
            $remote = trim($remoteLines[0] ?? '');

            return empty($local) || $local !== $remote;
        } finally {
            if ($sshKeyFile && file_exists($sshKeyFile)) {
                unlink($sshKeyFile);
            }
        }
    }

    /**
     * Build git authentication credentials for shell commands.
     * Returns the (possibly token-embedded) git URL, the env prefix for SSH keys,
     * and the temp SSH key file path (caller must delete on cleanup).
     */
    private function buildCredentials(Project $project): array
    {
        $gitUrl       = $project->git_url;
        $gitEnvPrefix = '';
        $sshKeyFile   = null;

        if ($project->gitCredential) {
            $cred = $project->gitCredential;
            if ($cred->type === 'token') {
                $gitUrl = preg_replace(
                    '#^(https?://)#',
                    '$1x-access-token:' . rawurlencode($cred->credential) . '@',
                    $gitUrl
                );
            } elseif ($cred->type === 'ssh_key') {
                $sshKeyFile = tempnam(sys_get_temp_dir(), 'lhp_key_');
                file_put_contents($sshKeyFile, rtrim($cred->credential) . "\n");
                chmod($sshKeyFile, 0600);
                $gitEnvPrefix = 'GIT_SSH_COMMAND='
                    . escapeshellarg('ssh -i ' . $sshKeyFile . ' -o StrictHostKeyChecking=accept-new -o BatchMode=yes')
                    . ' ';
            }
        }

        return compact('gitUrl', 'gitEnvPrefix', 'sshKeyFile');
    }
}
