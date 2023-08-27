<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gym_Trainer extends Model
{
    use HasFactory;

    protected $table = 'gym_trainers';

    protected $fillable = [
        'name',
        'image',
        'branch_id',
        'salary'
    ];

    public function gym_branch()
    {
        return $this->belongsTo(Gym_Branch::class,'branch_id');
    }
    public function review_Gym_Trainers()
    {
        return $this->hasMany(Review_Gym_Trainer::class,'gym_trainer_id');
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class,'gym_trainer_id');
    }

    public function finances()
    {
        return $this->hasMany(Finance::class,'gym_trainer_id');
    }
    public function vacations()
    {
        return $this->hasMany(Vacation::class,'gym_trainer_id');
    }
}
