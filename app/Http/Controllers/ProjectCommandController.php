<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectCommandRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectCommandController extends Controller
{
    private const PRESET_COMMANDS = [
        ['label' => 'Migrate',           'command' => 'php artisan migrate --force'],
        ['label' => 'Migrate:Fresh',     'command' => 'php artisan migrate:fresh --force'],
        ['label' => 'DB Seed',           'command' => 'php artisan db:seed --force'],
        ['label' => 'Cache Clear',       'command' => 'php artisan cache:clear'],
        ['label' => 'Config Cache',      'command' => 'php artisan config:cache'],
        ['label' => 'Config Clear',      'command' => 'php artisan config:clear'],
        ['label' => 'Route Cache',       'command' => 'php artisan route:cache'],
        ['label' => 'Route Clear',       'command' => 'php artisan route:clear'],
        ['label' => 'View Clear',        'command' => 'php artisan view:clear'],
        ['label' => 'Optimize',          'command' => 'php artisan optimize'],
        ['label' => 'Storage Link',      'command' => 'php artisan storage:link'],
        ['label' => 'Queue Restart',     'command' => 'php artisan queue:restart'],
        ['label' => 'Composer Install',  'command' => 'composer install --no-interaction --prefer-dist --optimize-autoloader'],
        ['label' => 'NPM Install',       'command' => 'npm ci'],
        ['label' => 'NPM Build',         'command' => 'npm run build'],
    ];

    // -------------------------------------------------------------------------

    public function index(Project $project)
    {
        $runs = $project->commandRuns()->latest()->paginate(15);

        return view('projects.commands.index', [
            'project' => $project,
            'runs'    => $runs,
            'presets' => self::PRESET_COMMANDS,
        ]);
    }

    // -------------------------------------------------------------------------

    public function run(Project $project, Request $request)
    {
        $request->validate([
            'command' => ['required', 'string', 'max:1000'],
            'label'   => ['nullable', 'string', 'max:100'],
        ]);

        $workDir = $this->resolveWorkDir($project);

        if (!$workDir || !is_dir($workDir)) {
            return redirect()->back()
                ->with('error', 'Project directory not found. '
                    . ($project->source_type === 'git'
                        ? 'Deploy the project first so the working directory exists.'
                        : 'Check that the local path is correct.'));
        }

        $runId      = uniqid('cmd_', true);
        $outputFile = storage_path('logs/cmd-' . $project->id . '-' . $runId . '.log');
        $exitFile   = storage_path('logs/cmd-' . $project->id . '-' . $runId . '.exit');

        /** @var ProjectCommandRun $commandRun */
        $commandRun = $project->commandRuns()->create([
            'command'        => $request->input('command'),
            'label'          => $request->input('label') ?: null,
            'status'         => 'running',
            'output_file'    => $outputFile,
            'exit_code_file' => $exitFile,
            'started_at'     => now(),
        ]);

        $innerCmd = '(cd ' . escapeshellarg($workDir)
            . ' && ' . $request->input('command') . ')'
            . ' > ' . escapeshellarg($outputFile) . ' 2>&1'
            . '; echo $? > ' . escapeshellarg($exitFile);

        $startCmd = 'nohup bash -c ' . escapeshellarg($innerCmd) . ' > /dev/null 2>&1 & echo $!';
        $pid      = (int) exec($startCmd);

        if ($pid > 0) {
            $commandRun->update(['pid' => $pid]);
            Log::info("[LaraHostPanel] {$project->name} (#{$project->id}): command run #{$commandRun->id} started with PID {$pid}.");
        } else {
            $commandRun->update(['status' => 'failed', 'completed_at' => now(), 'exit_code' => -1]);
            return redirect()->route('projects.commands.index', $project)
                ->with('error', 'Failed to start the command process.');
        }

        return redirect()->route('projects.commands.index', $project)
            ->with('success', 'Command started.')
            ->with('latest_run_id', $commandRun->id);
    }

    // -------------------------------------------------------------------------

    /**
     * Polling endpoint: returns current output + completion status as JSON.
     */
    public function output(Project $project, ProjectCommandRun $commandRun)
    {
        abort_if($commandRun->project_id !== $project->id, 404);

        $output   = '';
        $done     = false;
        $exitCode = null;
        $status   = $commandRun->status;

        // Safe path check before reading
        if ($commandRun->output_file) {
            $expectedBase = storage_path('logs');
            $realPath     = realpath($commandRun->output_file) ?: $commandRun->output_file;

            if (str_starts_with($realPath, $expectedBase) && file_exists($commandRun->output_file)) {
                $output = file_get_contents($commandRun->output_file) ?: '';
            }
        }

        if ($status === 'running') {
            $alive = $commandRun->pid > 0 && file_exists('/proc/' . $commandRun->pid);

            if (!$alive) {
                // Process ended — read exit code
                $exitCode = null;

                if ($commandRun->exit_code_file) {
                    $expectedBase = storage_path('logs');
                    $realPath     = realpath($commandRun->exit_code_file) ?: $commandRun->exit_code_file;

                    if (str_starts_with($realPath, $expectedBase) && file_exists($commandRun->exit_code_file)) {
                        $raw      = trim(file_get_contents($commandRun->exit_code_file));
                        $exitCode = is_numeric($raw) ? (int) $raw : null;
                    }
                }

                $status = ($exitCode === 0) ? 'success' : 'failed';

                $commandRun->update([
                    'status'       => $status,
                    'exit_code'    => $exitCode,
                    'completed_at' => now(),
                ]);

                $done = true;
            }
        } else {
            $done     = true;
            $exitCode = $commandRun->exit_code;
        }

        return response()->json([
            'output'    => $output,
            'status'    => $status,
            'exit_code' => $exitCode,
            'done'      => $done,
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * Kill a running command process. The polling endpoint will detect the exit
     * and update the status automatically.
     */
    public function stop(Project $project, ProjectCommandRun $commandRun)
    {
        abort_if($commandRun->project_id !== $project->id, 404);

        if ($commandRun->status === 'running' && $commandRun->pid > 0) {
            $pid = (int) $commandRun->pid;
            exec("kill -TERM {$pid} 2>/dev/null");
            exec("pkill -TERM -P {$pid} 2>/dev/null");
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------

    private function resolveWorkDir(Project $project): ?string
    {
        if ($project->source_type === 'local') {
            return $project->local_path ?: null;
        }

        // Git-sourced: use the deployment directory
        return storage_path('app/deployments/' . (int) $project->id);
    }
}
