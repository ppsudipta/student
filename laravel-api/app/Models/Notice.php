<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $table = 'notices';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'seen' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
