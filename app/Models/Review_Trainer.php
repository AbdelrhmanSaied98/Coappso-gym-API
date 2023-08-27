<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review_Trainer extends Model
{
    use HasFactory;

    protected $table = 'review_trainers';

    protected $fillable = [
        'user_id',
        'trainer_id',
        'rate',
        'feedback'
    ];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function trainer()
    {
        return $this->belongsTo(Trainer::class,'trainer_id');
    }
}
