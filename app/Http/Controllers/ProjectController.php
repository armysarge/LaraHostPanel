<?php

namespace App\Http\Controllers;

use App\Models\DeploymentLog;
use App\Models\GitCredential;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('gitCredential')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $credentials = GitCredential::orderBy('name')->get();

        return view('projects.create', compact('credentials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'source_type'          => ['required', Rule::in(['local', 'git'])],
            'local_path'           => ['nullable', 'string', 'max:500', 'required_if:source_type,local'],
            'git_url'              => ['nullable', 'string', 'max:500', 'required_if:source_type,git'],
            'branch'               => ['nullable', 'string', 'max:255'],
            'ip_address'           => ['required', 'ip'],
            'port'                 => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('projects', 'port')],
            'git_credential_id'    => ['nullable', 'exists:git_credentials,id'],
            'auto_deploy'          => ['boolean'],
            'auto_deploy_interval' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'auto_start'           => ['boolean'],
        ]);

        $validated['auto_deploy'] = $request->boolean('auto_deploy');
        $validated['auto_start'] = $request->boolean('auto_start');
        $validated['branch'] = $validated['branch'] ?? 'main';
        $validated['auto_deploy_interval'] = $validated['auto_deploy_interval'] ?? 5;

        $project = Project::create($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load(['gitCredential', 'deploymentLogs' => function ($q) {
            $q->orderByDesc('created_at')->limit(20);
        }]);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $credentials = GitCredential::orderBy('name')->get();

        return view('projects.edit', compact('project', 'credentials'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'source_type'          => ['required', Rule::in(['local', 'git'])],
            'local_path'           => ['nullable', 'string', 'max:500', 'required_if:source_type,local'],
            'git_url'              => ['nullable', 'string', 'max:500', 'required_if:source_type,git'],
            'branch'               => ['nullable', 'string', 'max:255'],
            'ip_address'           => ['required', 'ip'],
            'port'                 => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('projects', 'port')->ignore($project->id)],
            'git_credential_id'    => ['nullable', 'exists:git_credentials,id'],
            'auto_deploy'          => ['boolean'],
            'auto_deploy_interval' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'auto_start'           => ['boolean'],
        ]);

        $validated['auto_deploy'] = $request->boolean('auto_deploy');
        $validated['auto_start'] = $request->boolean('auto_start');
        $validated['branch'] = $validated['branch'] ?? 'main';
        $validated['auto_deploy_interval'] = $validated['auto_deploy_interval'] ?? 5;

        $project->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function start(Project $project)
    {
        if ($project->isRunning()) {
            return redirect()->back()->with('success', "\"$project->name\" is already running.");
        }

        if ($project->source_type === 'local' && $project->local_path) {
            // Expand ~ to the real home directory.
            $path = rtrim($project->local_path, '/');
            if (str_starts_with($path, '~/')) {
                $home = rtrim(posix_getpwuid(posix_getuid())['dir'] ?? ($_SERVER['HOME'] ?? ''), '/');
                $path = $home . substr($path, 1);
            }

            if (!is_dir($path)) {
                $message = "Project path does not exist: {$path}";
                $project->update(['status' => 'error', 'pid' => null]);
                Log::error("[LaraHostPanel] {$project->name} (#{$project->id}): {$message}");
                $project->deploymentLogs()->create([
                    'status'     => 'failed',
                    'output'     => $message,
                    'started_at' => now(),
                    'completed_at' => now(),
                ]);
                return redirect()->back()->with('error', $message);
            }

            $ip   = filter_var($project->ip_address, FILTER_VALIDATE_IP);
            $port = (int) $project->port;

            if (!$ip) {
                $message = "Invalid IP address configured for this project: {$project->ip_address}";
                $project->update(['status' => 'error', 'pid' => null]);
                Log::error("[LaraHostPanel] {$project->name} (#{$project->id}): {$message}");
                $project->deploymentLogs()->create([
                    'status'       => 'failed',
                    'output'       => $message,
                    'started_at'   => now(),
                    'completed_at' => now(),
                ]);
                return redirect()->back()->with('error', $message);
            }

            $logFile = storage_path('logs/project-' . $project->id . '.log');
            $startedAt = now();

            if (file_exists($path . '/artisan')) {
                $serve = "php artisan serve --host={$ip} --port={$port}";
            } elseif (is_dir($path . '/public')) {
                $serve = 'php -S ' . $ip . ':' . $port . ' -t ' . escapeshellarg($path . '/public');
            } else {
                $serve = 'php -S ' . $ip . ':' . $port;
            }

            $cmd = 'cd ' . escapeshellarg($path)
                // env -i gives the child process a clean environment so it doesn't
                // inherit LaraHostPanel's DB_CONNECTION, APP_KEY, etc., which would
                // prevent the project's own .env from loading correctly.
                . ' && nohup env -i'
                . ' HOME=' . escapeshellarg($_SERVER['HOME'] ?? posix_getpwuid(posix_getuid())['dir'])
                . ' PATH=' . escapeshellarg($_SERVER['PATH'] ?? '/usr/local/bin:/usr/bin:/bin')
                . ' ' . $serve
                . ' > ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
            $pid = (int) exec($cmd);

            if ($pid > 0) {
                $project->update([
                    'status'           => 'running',
                    'pid'              => $pid,
                    'last_deployed_at' => now(),
                ]);
                Log::info("[LaraHostPanel] {$project->name} (#{$project->id}) started with PID {$pid}.");
                $project->deploymentLogs()->create([
                    'status'       => 'success',
                    'output'       => "Started with PID {$pid}.\nServing: {$serve}",
                    'started_at'   => $startedAt,
                    'completed_at' => now(),
                ]);
            } else {
                $message = "Failed to start \"{$project->name}\". Check storage/logs/project-{$project->id}.log for details.";
                $project->update(['status' => 'error', 'pid' => null]);
                Log::error("[LaraHostPanel] {$project->name} (#{$project->id}): process did not start. Command: {$cmd}");
                $project->deploymentLogs()->create([
                    'status'       => 'failed',
                    'output'       => $message,
                    'started_at'   => $startedAt,
                    'completed_at' => now(),
                ]);
                return redirect()->back()->with('error', $message);
            }
        } else {
            // Git-sourced projects — clone / pull then start the server
            $deployed = $this->deployGitProject($project);
            if (!$deployed) {
                return redirect()->back()->with('error', "Failed to deploy \"{$project->name}\". Check deployment logs for details.");
            }
        }

        return redirect()->back()->with('success', "\"$project->name\" started.");
    }

    public function deploy(Project $project)
    {
        if ($project->source_type !== 'git') {
            return redirect()->back()->with('error', 'Manual re-deploy is only available for git-sourced projects.');
        }

        // Kill any running process first
        if ($project->pid) {
            $pid = (int) $project->pid;
            exec("kill -TERM {$pid} 2>/dev/null");
            exec("pkill -TERM -P {$pid} 2>/dev/null");
        }

        $project->update(['status' => 'deploying', 'pid' => null]);

        $deployed = $this->deployGitProject($project);

        if (!$deployed) {
            return redirect()->back()->with('error', "Re-deploy of \"{$project->name}\" failed. Check deployment logs for details.");
        }

        return redirect()->back()->with('success', "\"$project->name\" re-deployed successfully.");
    }

    // -------------------------------------------------------------------------
    // Git deployment helper
    // -------------------------------------------------------------------------

    /**
     * Clone (or pull) a git project and start its PHP server.
     * Returns true on success, false on failure (side-effects: updates project
     * status and writes a DeploymentLog record).
     */
    private function deployGitProject(Project $project): bool
    {
        $startedAt  = now();
        $deployPath = storage_path('app/deployments/' . (int) $project->id);
        $rawBranch  = $project->branch ?? 'main';

        // ---- Build git credentials ------------------------------------------
        $gitUrl       = $project->git_url;
        $gitEnvPrefix = '';
        $sshKeyFile   = null;

        if ($project->gitCredential) {
            $cred = $project->gitCredential;
            if ($cred->type === 'token') {
                // Embed token into the HTTPS URL
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

        // ---- Clone or pull --------------------------------------------------
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
                // Guard: only delete if the path is inside our expected base directory.
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

        // ---- Resolve commit hash --------------------------------------------
        $hashLines = [];
        exec('cd ' . escapeshellarg($deployPath) . ' && git rev-parse HEAD 2>/dev/null', $hashLines);
        $commitHash = trim($hashLines[0] ?? '');

        // ---- Start the PHP server -------------------------------------------
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

        // Server failed to start
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

    public function stop(Project $project)
    {
        if (!$project->isRunning()) {
            return redirect()->back()->with('success', "\"$project->name\" is already stopped.");
        }

        if ($project->pid) {
            $pid = (int) $project->pid;
            exec("kill -TERM {$pid} 2>/dev/null");
            // Also terminate child processes (e.g. spawned by artisan serve)
            exec("pkill -TERM -P {$pid} 2>/dev/null");
        }

        $project->update(['status' => 'stopped', 'pid' => null]);

        return redirect()->back()->with('success', "\"{$project->name}\" stopped.");
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted.');
    }

    // -------------------------------------------------------------------------
    // .env editor
    // -------------------------------------------------------------------------

    private function resolveEnvPath(Project $project): ?string
    {
        $base = $project->source_type === 'local' ? $project->local_path : null;
        if (!$base) return null;

        // Expand tilde
        if (str_starts_with($base, '~/')) {
            $home = rtrim(posix_getpwuid(posix_getuid())['dir'] ?? ($_SERVER['HOME'] ?? ''), '/');
            $base = $home . substr($base, 1);
        }

        $envPath = rtrim($base, '/') . '/.env';
        return file_exists($envPath) ? $envPath : null;
    }

    public function envEdit(Project $project)
    {
        $envPath = $this->resolveEnvPath($project);

        if (!$envPath) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'No .env file found for this project.');
        }

        $contents = file_get_contents($envPath);

        return view('projects.env', compact('project', 'contents', 'envPath'));
    }

    public function envUpdate(Request $request, Project $project)
    {
        $envPath = $this->resolveEnvPath($project);

        if (!$envPath) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'No .env file found for this project.');
        }

        $contents = $request->input('contents', '');

        // Ensure the file ends with a newline
        $contents = rtrim($contents) . "\n";

        file_put_contents($envPath, $contents);

        Log::info("[LaraHostPanel] .env updated for {$project->name} (#{$project->id}).");

        return redirect()->route('projects.env.edit', $project)
            ->with('success', '.env saved successfully.');
    }
}
