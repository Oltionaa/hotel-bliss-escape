<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // ← ADD THIS

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // ← ADD HasApiTokens here too


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

}
