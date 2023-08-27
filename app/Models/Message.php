<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $fillable = [
        'content_type',
        'content',
        'user_id',
        'gym_id',
        'trainer_id',
        'sender_type',
        'receiver_type',
    ];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function gym()
    {
        return $this->belongsTo(Gym::class,'gym_id');
    }
    public function trainer()
    {
        return $this->belongsTo(Trainer::class,'trainer_id');
    }
}
