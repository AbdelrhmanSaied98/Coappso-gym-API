<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review_Gym_Trainer extends Model
{
    use HasFactory;

    protected $table = 'review_gym_trainers';
    protected $fillable = [
        'user_id',
        'gym_trainer_id',
        'rate',
        'feedback'
    ];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function gym_trainer()
    {
        return $this->belongsTo(Gym_Trainer::class,'gym_trainer_id');
    }
}
