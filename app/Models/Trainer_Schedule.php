<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer_Schedule extends Model
{
    use HasFactory;

    protected $table = 'trainer_schedules';

    protected $fillable = [
        'day',
        'time_from',
        'time_to',
        'trainer_id',
    ];
    public function trainer()
    {
        return $this->belongsTo(Trainer::class,'trainer_id');
    }
}
