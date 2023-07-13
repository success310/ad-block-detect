<?php

namespace App\Services;

use App\Models\Paste;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

class PasteService
{
    public function getAll($limit, $search)
    {
        $user = auth()->user();
        $key = 'pastes.all.' . ($user->id ?? 'guest') . ".$limit.$search";

        return Cache::remember($key, 60, function () use ($user, $limit, $search) {
            if ($user->isSuperAdmin())
                $query = Paste::with('user');
            else
                $query = $user->pastes();
            if ($search)
                $query = $query->where('title', 'LIKE', '%' . $search . '%');

            return $query->latest()->paginate($limit);
        });
    }
    public function create($slug, $title, $isLinkClickable, $allowEmbedding, $allowRaw, $content, $bgColor, $textColor, $boxColor, $detailsColor, $expiration, $timezone, $password, $videoEmbed, $user)
    {
        $customSlug = $user && $slug ? $slug : Str::random(10);
        $args = [
            'isLinksClickable' => $isLinkClickable,
            'content' => $content,
            'slug' => $customSlug,
            'timezone' => $timezone,
            'title' => $title ?? '',
            'bgColor' => $bgColor,
            'textColor' => $textColor,
            'expiration' => $expiration,
            'detailsColor' => $detailsColor,
            'boxColor' => $boxColor,
            'allowEmbed' => $allowEmbedding,
            'allowRaw' => $allowRaw,
            'password' => is_null($password) ? $password : bcrypt($password),
            'videoEmbed' => $videoEmbed
        ];
        if ($user)
            $paste = $user->pastes()->create($args);
        else
            $paste = Paste::create($args);


        return $paste;
    }
    public function update(Paste $paste, $title, $isLinkClickable, $allowEmbedding, $allowRaw, $content, $bgColor, $textColor, $boxColor, $detailsColor, $expiration, $timezone, $slug, $password, $videoEmbed)
    {
        $paste->content = $content;
        $paste->bgColor = $bgColor;
        $paste->title = $title ?? '';
        $paste->textColor = $textColor;
        $paste->isLinksClickable = $isLinkClickable;
        $paste->expiration = $expiration;
        $paste->timezone = $timezone;
        $paste->boxColor = $boxColor;
        $paste->allowEmbed = $allowEmbedding;
        $paste->detailsColor = $detailsColor;
        $paste->password = is_null($password) ? $password : bcrypt($password);
        $paste->allowRaw = $allowRaw;
        $paste->videoEmbed = $videoEmbed;
        if ($slug)
            $paste->slug = $slug;
        $paste->save();

        return $paste;
    }
    public function findBySlug($slug)
    {
        return Cache::remember("paste.slug.$slug", 60, function () use ($slug) {
            return Paste::with('user')->where('slug', $slug)->first();
        });
    }
    public function findById($id, $search)
    {
        $query = Paste::with('user');
        if ($id == 'guest')
            $query = $query->where('user_id', null);
        else if ($id != 'all')
            $query = $query->where('user_id', $id);

        if ($search)
            $query = $query->where('title', 'LIKE', '%' . $search . '%');

        return $query;
    }
    public function recordView(Request $request, $paste)
    {
        $cookieKey = "viewed_{$paste->getTable()}_{$paste->getKey()}";
        $viewed = $request->cookie($cookieKey);

        if (!$viewed) {
            views($paste)->record();
            $cookie = Cookie::make($cookieKey, true, 3600); // 3600 minutes
            return $cookie;
        }

        return null;
    }
}
