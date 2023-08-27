<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notification';
    protected $fillable = [
        'notification',
        'user_type',
        'user_id',
        'content_type',
        'content_id',
        'seen'
    ];
}
