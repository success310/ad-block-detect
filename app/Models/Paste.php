<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\returnSelf;

class Paste extends Model implements Viewable
{
    use HasFactory, InteractsWithViews;
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'allowEmbed' => 'boolean',
        'isLinksClickable' => 'boolean',
    ];
    protected $fillable = ['allowEmbed', 'isLinksClickable', 'content', 'bgColor', 'boxColor', 'textColor', 'detailsColor', 'expiration', 'timezone', 'title', 'slug', 'password', 'allowRaw', 'videoEmbed'];
    protected $appends = ['views', 'isProtected'];
    protected $hidden = [
        'password',
    ];
    //define accessor
    public function getViewsAttribute()
    {
        return views($this)->count();
    }

    public function getIsProtectedAttribute()
    {
        return $this->password !== null;
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }


    public  function getExpirationAttribute($value)
    {
        if (!$value) return null;

        $pasteDate = date('Y-m-d H:i:s', strtotime($value));

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $pasteDate, 'UTC');
        $date->setTimezone($this->timezone);
        return $date->format('Y-m-d H:i');
    }

    public function setExpirationAttribute($value)
    {
        if (!$value) {
            $this->attributes['expiration'] = null;
            return;
        }
        $pasteDate = date('Y-m-d H:i:s', strtotime($value));
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $pasteDate, $this->timezone);
        $date->setTimezone('UTC');
        $this->attributes['expiration'] = $date;
    }
    public function isLinkExpired()
    {
        if (!$this->expiration) return false;
        $date = Carbon::createFromFormat('Y-m-d H:i', $this->expiration, $this->timezone);
        return $date->isPast();
    }

    protected static function booted()
    {
        static::created(function ($paste) {
            Cache::forget("paste.slug.{$paste->slug}");
            Cache::tags('pastes.all')->flush();
        });

        static::updated(function ($paste) {
            Cache::forget("paste.slug.{$paste->slug}");
            Cache::tags('pastes.all')->flush();
        });

        static::deleted(function ($paste) {
            Cache::forget("paste.slug.{$paste->slug}");
            Cache::tags('pastes.all')->flush();
        });
    }
}
