<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCommandRun extends Model
{
    protected $fillable = [
        'project_id',
        'command',
        'label',
        'status',
        'pid',
        'output_file',
        'exit_code_file',
        'exit_code',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'pid'          => 'integer',
            'exit_code'    => 'integer',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Read the current output from disk, validating the path is within the
     * expected storage directory to prevent path traversal.
     */
    public function currentOutput(): string
    {
        if (!$this->output_file) {
            return '';
        }

        $expectedBase = storage_path('logs');
        $realPath     = realpath($this->output_file) ?: $this->output_file;

        if (!str_starts_with($realPath, $expectedBase)) {
            return '';
        }

        if (!file_exists($this->output_file)) {
            return '';
        }

        return file_get_contents($this->output_file) ?: '';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running'
            && $this->pid > 0
            && file_exists('/proc/' . $this->pid);
    }
}
