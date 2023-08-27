<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer_Media extends Model
{
    use HasFactory;

    protected $table = 'trainer_medias';

    protected $fillable = [
        'file',
        'trainer_id',
        'type',
    ];
    public function trainer()
    {
        return $this->belongsTo(Trainer::class,'trainer_id');
    }

}
