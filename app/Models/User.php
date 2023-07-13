<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'total_views',
        'earnings',
        'paypal_email'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'allowAffiliate' => 'boolean'
    ];
    public function pastes()
    {
        return $this->hasMany('App\Models\Paste');
    }
    public function keys()
    {
        return $this->hasMany('App\Models\Key');
    }
    public function payouts()
    {
        return $this->hasMany('App\Models\Payout');
    }
    public function isSuperAdmin()
    {
        return !!$this->isAdmin;
    }
    public function adlinks()
    {
        return $this->belongsTo('App\Models\Adlink');
    }
    public function updatePassword($newPassword)
    {
        if (!$newPassword) return false;
        $this->password = Hash::make($newPassword);
        return $this->save();
    }
}
