<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking_Gym extends Model
{
    use HasFactory;

    protected $table = 'booking_gyms';

    protected $fillable = [
        'user_id',
        'date',
        'branch_id',
        'isWithTrainer',
        'classes',
        'price',
        'attendance_status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function gym_branch()
    {
        return $this->belongsTo(Gym_Branch::class,'branch_id');
    }


}
