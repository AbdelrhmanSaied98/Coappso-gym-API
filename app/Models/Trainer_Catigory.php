<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer_Catigory extends Model
{
    use HasFactory;
    protected $table = 'trainer_categories';

    protected $fillable = [
        'trainer_id',
        'category_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class,'category_id');
    }
    public function trainer()
    {
        return $this->belongsTo(Trainer::class,'trainer_id');
    }

}
