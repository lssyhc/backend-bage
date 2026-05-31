<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeedRecord extends Model
{
    protected $fillable = [
        'key',
        'recordable_type',
        'recordable_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function recordable()
    {
        return $this->morphTo();
    }
}
