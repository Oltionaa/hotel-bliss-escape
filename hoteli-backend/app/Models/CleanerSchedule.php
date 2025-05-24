<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleanerSchedule extends Model
{
    use HasFactory;

    protected $table = 'cleaner_schedules'; // Lidh modelin me emrin e saktë të tabelës

    protected $fillable = [
        'cleaner_id',
        'work_date',
        'shift_start',
        'shift_end',
        'status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'shift_start' => 'datetime',
        'shift_end' => 'datetime',
    ];

    // Lidhja me modelin User (kush është pastruesi)
    public function cleaner()
    {
        return $this->belongsTo(User::class, 'cleaner_id');
    }
}