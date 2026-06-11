<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'source_type',
        'local_path',
        'git_url',
        'branch',
        'ip_address',
        'port',
        'status',
        'app_server',
        'pid',
        'container_id',
        'auto_deploy',
        'auto_deploy_interval',
        'auto_start',
        'last_commit_hash',
        'last_deployed_at',
        'last_checked_at',
        'git_credential_id',
    ];

    protected function casts(): array
    {
        return [
            'auto_deploy' => 'boolean',
            'auto_deploy_interval' => 'integer',
            'auto_start' => 'boolean',
            'port' => 'integer',
            'pid' => 'integer',
            'last_deployed_at' => 'datetime',
            'last_checked_at'  => 'datetime',
        ];
    }

    public function gitCredential(): BelongsTo
    {
        return $this->belongsTo(GitCredential::class);
    }

    public function deploymentLogs(): HasMany
    {
        return $this->hasMany(DeploymentLog::class);
    }

    public function commandRuns(): HasMany
    {
        return $this->hasMany(ProjectCommandRun::class);
    }

    public function latestDeployment(): HasMany
    {
        return $this->hasMany(DeploymentLog::class)->latestOfMany();
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isGitSource(): bool
    {
        return $this->source_type === 'git';
    }

    /**
     * Resolve the directory this project runs from — its local path for
     * locally-sourced projects (with `~` expanded), or the git deployment
     * clone under storage/app/deployments/{id}.
     */
    public function workingDirectory(): ?string
    {
        if ($this->source_type === 'local') {
            $path = rtrim($this->local_path ?? '', '/');

            if (str_starts_with($path, '~/')) {
                $home = rtrim($_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? '', '/');
                $path = $home . substr($path, 1);
            }

            return $path ?: null;
        }

        return storage_path('app/deployments/' . (int) $this->id);
    }

    /**
     * Whether Laravel Octane (with the RoadRunner binary) is installed in
     * this project's working directory.
     */
    public function hasOctaneInstalled(): bool
    {
        $path = $this->workingDirectory();

        return $path !== null && file_exists($path . '/vendor/bin/rr');
    }

    /**
     * Build the shell command used to serve this project, taking into
     * account the configured app server (plain `artisan serve` vs Octane).
     */
    public function buildServeCommand(string $path): string
    {
        $ip   = filter_var($this->ip_address, FILTER_VALIDATE_IP) ?: $this->ip_address;
        $port = (int) $this->port;

        if ($this->app_server === 'octane'
            && file_exists($path . '/artisan')
            && file_exists($path . '/vendor/bin/rr')) {
            return "php artisan octane:start --server=roadrunner --host={$ip} --port={$port}";
        }

        if (file_exists($path . '/artisan')) {
            return "php artisan serve --host={$ip} --port={$port}";
        }

        if (is_dir($path . '/public')) {
            return 'php -S ' . $ip . ':' . $port . ' -t ' . escapeshellarg($path . '/public');
        }

        return 'php -S ' . $ip . ':' . $port;
    }
}
