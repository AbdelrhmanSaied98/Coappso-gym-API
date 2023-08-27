<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Trainer extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = 'trainers';

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

    public function categories()
    {
        return $this->hasMany(Trainer_Catigory::class,'trainer_id');
    }
    public function medias()
    {
        return $this->hasMany(Trainer_Media::class,'trainer_id');
    }
    public function booking_trainers()
    {
        return $this->hasMany(Booking_Trainer::class,'trainer_id');
    }
    public function trainer_schedules()
    {
        return $this->hasMany(Trainer_Schedule::class,'trainer_id');
    }
    public function favorite_users()
    {
        return $this->hasMany(Favorite_Trainer::class,'trainer_id');
    }
    public function review_Trainers()
    {
        return $this->hasMany(Review_Trainer::class,'trainer_id');
    }

}
