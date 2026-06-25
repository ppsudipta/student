<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMaterial extends Model
{
    protected $table = 'student_materials';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'upload_date' => 'datetime',
        ];
    }
}
