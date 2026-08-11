<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'company', 'website', 'status', 'score', 'enrichment', 'enriched_at'];

    protected $casts = [
        'score' => 'integer',
        'enrichment' => 'array',
        'enriched_at' => 'datetime',
    ];

    public function automationRuns(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }
}
