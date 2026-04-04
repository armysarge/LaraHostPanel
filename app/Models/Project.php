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
        'pid',
        'container_id',
        'auto_deploy',
        'auto_deploy_interval',
        'auto_start',
        'last_commit_hash',
        'last_deployed_at',
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
}
