<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacation extends Model
{
    use HasFactory;

    protected $table = 'vacations';

    protected $fillable = [
        'date',
        'type',
        'gym_trainer_id',
    ];
    public function gym_trainer()
    {
        return $this->belongsTo(Gym_Trainer::class,'gym_trainer_id');
    }
}
