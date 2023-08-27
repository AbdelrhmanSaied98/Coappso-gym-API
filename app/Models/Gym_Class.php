<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gym_Class extends Model
{
    use HasFactory;

    protected $table = 'gym_classes';

    protected $fillable = [
        'name',
        'image',
        'price',
        'duration',
        'branch_id',
        'is_offer',
        'durationOffer',
        'new_price',
        'trainer_name',
        'trainer_image'
    ];

    public function gym_branch()
    {
        return $this->belongsTo(Gym_Branch::class,'branch_id');
    }



}
