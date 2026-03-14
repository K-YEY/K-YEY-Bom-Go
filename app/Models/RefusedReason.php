<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefusedReason extends Model
{
    protected $fillable = [
        'reason',
        'status',
        'is_active',
        'is_clear',
        'is_return',
        'is_edit_amount',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_clear'  => 'boolean',
            'is_return' => 'boolean',
            'is_edit_amount' => 'boolean',
        ];
    }
}
