<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adlink extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'link'];
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
