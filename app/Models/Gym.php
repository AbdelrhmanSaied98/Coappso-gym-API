<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Gym extends Authenticatable implements JWTSubject
{
    use HasFactory;


    protected $table = 'gyms';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'device_token',
        'image',
        'verification_code',
        'isBlocked',
        'ban_times',
        'refresh_token',
    ];

    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function gym_branches()
    {
        return $this->hasMany(Gym_Branch::class,'gym_id');
    }
    public function favorite_users()
    {
        return $this->hasMany(Favorite_Gym::class,'gym_id');
    }
    public function reviews()
    {
        return $this->hasMany(Review_Gym::class,'gym_id');
    }
}
