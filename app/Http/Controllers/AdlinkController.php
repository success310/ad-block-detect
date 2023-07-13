<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdlinkRequest;
use App\Models\Adlink;
use Illuminate\Http\Request;

class AdlinkController extends Controller
{
    public function index()
    {
        $regularAdlink = Adlink::where('user_id', null)->get();
        $customLinks = Adlink::where('user_id', '!=', null)->get();
        return response()->json(['customLinks' => $customLinks, 'regularAdlink' => $regularAdlink]);
    }
    public function store(StoreAdlinkRequest $request)
    {
        $fields = $request->all();

        Adlink::truncate();


        foreach ($fields['regularAdlink'] as $item) {
            if (!$item['link']) continue;

            Adlink::create(['user_id' => null, 'link' => $item['link']]);
        }

        foreach ($fields['customLinks'] as $item) {
            if (!$item['link']) continue;

            $link = Adlink::where('user_id', $item['user_id'])->first();
            if (!$link) {
                Adlink::create(['user_id' => $item['user_id'], 'link' => $item['link']]);
                continue;
            }
            $link->link = $item['link'];
            $link->save();
        }
    }
}
