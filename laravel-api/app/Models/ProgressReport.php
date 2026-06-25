<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressReport extends Model
{
    protected $table = 'progress_reports';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'marks_obtained' => 'float',
            'marks_out_of' => 'float',
            'report_date' => 'date',
            'created_at' => 'datetime',
        ];
    }
}
