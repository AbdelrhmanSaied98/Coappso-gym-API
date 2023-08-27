<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $table = 'attendences';

    protected $fillable = [
        'type',
        'time_attendance',
        'time_checkout',
        'absent_type',
        'date',
        'gym_trainer_id',
    ];
    public function gym_trainer()
    {
        return $this->belongsTo(Gym_Trainer::class,'gym_trainer_id');
    }
}
