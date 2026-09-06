<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDeviceToken extends Model
{
    protected $table = 'student_device_tokens';

    protected $fillable = [
        'student_id',
        'token',
        'platform',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
