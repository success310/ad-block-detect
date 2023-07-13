<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except('index');
    }
    public function update(Request $request)
    {
        if (auth()->user()->isSuperAdmin()) {
            $fields = $request->all();
            Setting::truncate();
            foreach ($fields as $key => $value) {
                if ($value == "" && $key !== 'toggleAdBlockAlert') continue;
                $setting = new Setting();
                $setting->key = $key;
                $setting->value = $value;
                $setting->save();
            }
            return Setting::all();
        }
    }
    public function index()
    {
        $settings = Setting::all();
        return response()->json($settings);
    }
}
