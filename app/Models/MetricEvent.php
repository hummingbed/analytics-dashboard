<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetricEvent extends Model
{
    protected $fillable = [
        'event_id',
        'type',
        'value',
        'source',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
