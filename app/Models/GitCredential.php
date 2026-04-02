<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'credential',
    ];

    protected function casts(): array
    {
        return [
            'credential' => 'encrypted',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
