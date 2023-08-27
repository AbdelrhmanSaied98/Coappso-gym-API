<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite_Gym extends Model
{
    use HasFactory;

    protected $table = 'favorite_gyms';

    protected $fillable = [
        'user_id',
        'gym_id'
    ];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function gym()
    {
        return $this->belongsTo(Gym::class,'gym_id');
    }
}
