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

        ['gitUrl' => $gitUrl, 'sshKeyFile' => $sshKeyFile] =
            $this->buildCredentials($project);

        // -c flags are git command-line arguments (work on all OSes).
        $gitBin  = 'git -c credential.helper= -c core.askpass=';
        $cd      = PHP_OS_FAMILY === 'Windows' ? 'cd /d' : 'cd';  // /d changes drive on Windows
        $devNull = PHP_OS_FAMILY === 'Windows' ? '2>NUL'  : '2>/dev/null';

        $cmdOutput = [];
        $exitCode  = 0;

        try {
            if (is_dir($deployPath . '/.git')) {
                // Update the remote URL so a rotated token takes effect immediately
                $setUrlCmd = $cd . ' ' . escapeshellarg($deployPath)
                    . ' && ' . $gitBin . ' remote set-url origin ' . escapeshellarg($gitUrl) . ' 2>&1';
                exec($setUrlCmd);

                // Pull latest commits on the target branch
                $cmd = $cd . ' ' . escapeshellarg($deployPath)
                    . ' && ' . $gitBin . ' fetch origin 2>&1'
                    . ' && ' . $gitBin . ' checkout ' . escapeshellarg($rawBranch) . ' 2>&1'
                    . ' && ' . $gitBin . ' reset --hard ' . escapeshellarg('origin/' . $rawBranch) . ' 2>&1';
                exec($cmd, $cmdOutput, $exitCode);
            } else {
                // Remove any partial/failed clone before starting fresh.
                $expectedBase = storage_path('app/deployments');
                if (is_dir($deployPath) && str_starts_with(realpath($deployPath) ?: $deployPath, $expectedBase)) {
                    $this->deleteDirectory($deployPath);
                }
                @mkdir($deployPath, 0755, true);

                $cmd = $gitBin
                    . ' clone --branch ' . escapeshellarg($rawBranch)
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
            $hint = (!$project->gitCredential && preg_match('#^https?://#', $project->git_url))
                ? "\n\nHint: This is an HTTPS URL with no credential configured. If the repository is private, go to the Credentials page, create a Personal Access Token credential, then edit this project and select it."
                : '';
            $project->deploymentLogs()->create([
                'status'       => 'failed',
                'output'       => "Git operation failed (exit {$exitCode}):\n{$gitOutput}{$hint}",
                'started_at'   => $startedAt,
                'completed_at' => now(),
            ]);
            return false;
        }

        // Resolve commit hash
        $hashLines = [];
        exec($cd . ' ' . escapeshellarg($deployPath) . ' && git rev-parse HEAD ' . $devNull, $hashLines);
        $commitHash = trim($hashLines[0] ?? '');

        // Create public/storage symlink for Laravel projects
        if (file_exists($deployPath . '/artisan')) {
            exec('cd ' . escapeshellarg($deployPath) . ' && php artisan storage:link --force 2>&1');
        }

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

        $pid = $this->spawnServer($serve, $deployPath, $logFile);

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

        ['gitUrl' => $gitUrl, 'sshKeyFile' => $sshKeyFile] =
            $this->buildCredentials($project);

        $gitBin  = 'git -c credential.helper= -c core.askpass=';
        $cd      = PHP_OS_FAMILY === 'Windows' ? 'cd /d' : 'cd';
        $devNull = PHP_OS_FAMILY === 'Windows' ? '2>NUL'  : '2>/dev/null';

        try {
            // Update remote URL so a rotated token is picked up immediately
            exec($cd . ' ' . escapeshellarg($deployPath) . ' && ' . $gitBin . ' remote set-url origin ' . escapeshellarg($gitUrl) . ' 2>&1');

            $fetchOutput = [];
            $exitCode    = 0;
            exec(
                $cd . ' ' . escapeshellarg($deployPath) . ' && ' . $gitBin . ' fetch origin 2>&1',
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
            exec($cd . ' ' . escapeshellarg($deployPath) . ' && git rev-parse HEAD ' . $devNull, $localLines);
            exec($cd . ' ' . escapeshellarg($deployPath) . ' && git rev-parse ' . escapeshellarg($branchRef) . ' ' . $devNull, $remoteLines);

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
        // Use putenv() so child processes (exec'd git) inherit the vars.
        // Shell VAR=value prefix syntax does not work on Windows cmd.exe.
        putenv('GIT_TERMINAL_PROMPT=0');  // suppress all interactive prompts
        putenv('GIT_ASKPASS=');           // disable askpass helper (incl. Windows GCM)

        $gitUrl     = $project->git_url;
        $sshKeyFile = null;

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
                putenv('GIT_SSH_COMMAND=ssh -i ' . $sshKeyFile . ' -o StrictHostKeyChecking=accept-new -o BatchMode=yes');
            }
        } elseif (preg_match('#^https?://#', $gitUrl)) {
            Log::warning("[LaraHostPanel] {$project->name} (#{$project->id}): HTTPS git URL with no credential — will fail for private repos. Add a credential on the Credentials page.");
        }

        return compact('gitUrl', 'sshKeyFile');
    }

    /**
     * Start the PHP development server as a detached background process.
     * Returns the PID of the spawned process (0 if unavailable on this platform).
     */
    private function spawnServer(string $serve, string $deployPath, string $logFile): int
    {
        @mkdir(dirname($logFile), 0755, true);

        if (PHP_OS_FAMILY !== 'Windows') {
            // Linux/macOS: shell & operator creates a truly detached process.
            $cmd = 'cd ' . escapeshellarg($deployPath)
                 . ' && nohup env -i'
                 . ' HOME=' . escapeshellarg($_SERVER['HOME'] ?? '/tmp')
                 . ' PATH=' . escapeshellarg($_SERVER['PATH'] ?? '/usr/local/bin:/usr/bin:/bin')
                 . ' ' . $serve
                 . ' > ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
            return (int) trim(exec($cmd));
        }

        // Windows: use PowerShell -EncodedCommand to avoid all cmd.exe quoting issues.
        // Start-Process -PassThru returns the new process object so we can read its PID.
        $psScript = '$p = Start-Process -PassThru -WindowStyle Hidden'
                  . ' -WorkingDirectory "' . $deployPath . '"'
                  . ' cmd -ArgumentList "/c ' . $serve . ' >> `"' . $logFile . '`" 2>&1";'
                  . ' Write-Output $p.Id';
        $encoded  = base64_encode(mb_convert_encoding($psScript, 'UTF-16LE', 'UTF-8'));
        return (int) trim(exec('powershell -NoProfile -NonInteractive -EncodedCommand ' . $encoded));
    }

    /**
     * Recursively delete a directory — cross-platform alternative to `rm -rf`.
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
