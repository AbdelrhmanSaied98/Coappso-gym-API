<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;



    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'age',
        'height',
        'weight',
        'device_token',
        'image',
        'location',
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
    public function booking()
    {
        return $this->hasMany(Booking_Gym::class,'user_id');
    }
    public function booking_trainers()
    {
        return $this->hasMany(Booking_Trainer::class,'user_id');
    }
    public function favorite_gyms()
    {
        return $this->hasMany(Favorite_Gym::class,'user_id');
    }
    public function favorite_trainers()
    {
        return $this->hasMany(Favorite_Trainer::class,'user_id');
    }

    public function review_Gyms()
    {
        return $this->hasMany(Review_Gym::class,'user_id');
    }
    public function review_Gym_Trainers()
    {
        return $this->hasMany(Review_Gym_Trainer::class,'user_id');
    }
    public function review_Trainers()
    {
        return $this->hasMany(Review_Trainer::class,'user_id');
    }

}
