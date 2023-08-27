<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gym_Branch extends Model
{
    use HasFactory;

    protected $table = 'gym_branches';

    protected $fillable = [
        'name',
        'time_from',
        'time_to',
        'address',
        'location',
        'gym_id'
    ];

    public function gym()
    {
        return $this->belongsTo(Gym::class,'gym_id');
    }
    public function gym_classes()
    {
        return $this->hasMany(Gym_Class::class,'branch_id');
    }
    public function gym_trainers()
    {
        return $this->hasMany(Gym_Trainer::class,'branch_id');
    }
    public function booking()
    {
        return $this->hasMany(Booking_Gym::class,'branch_id');
    }

}
