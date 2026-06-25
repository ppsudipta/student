<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';

    public $timestamps = false;

    protected $hidden = [
        'password',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_fees' => 'decimal:2',
            'paid_fees' => 'decimal:2',
            'due_fees' => 'decimal:2',
        ];
    }
}
