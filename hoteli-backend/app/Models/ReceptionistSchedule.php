<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 

class ReceptionistSchedule extends Model
{
    use HasFactory; 

    protected $fillable = [
        'receptionist_id',
        'work_date',
        'shift_start',
        'shift_end',
        'status',
        'check_in',
        'check_out',
    ];

    public function receptionist()
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }
}