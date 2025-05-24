<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', 
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function schedules()
    {
        return $this->hasMany(ReceptionistSchedule::class, 'receptionist_id');
    }
    public function cleanerSchedules()
{
    return $this->hasMany(CleanerSchedule::class, 'cleaner_id');
}
}