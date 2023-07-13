<?php

namespace App\Services;

use App\Models\Adlink;
use App\Models\Paste;
use App\Models\User;
use Illuminate\Support\Str;
use Auth;
use Laravel\Sanctum\Guard;

class AdService
{
    public function getDefaultAdlink()
    {
        $entry = Adlink::where('user_id', null)->first();
        return $entry ? $entry->link : null;
    }
    public function getUserAdlink($userId = null)
    {
        $defaultEntries = Adlink::where('user_id', null)->pluck('link');

        if (!$userId) return $defaultEntries;


        $userCustomEntry = Adlink::where('user_id', $userId)->first();
        if ($userCustomEntry)
            return [$userCustomEntry->link];

        return  $defaultEntries;
    }
}
