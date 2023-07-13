<?php

namespace App\Http\Controllers;

use App\Models\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KeyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            if (!Gate::allows('is-admin')) {
                return abort(403, 'unauthorized');
            }

            return $next($request);
        });
    }
    public function index()
    {
        return Key::with(['user' => function ($query) {
            $query->select('id', 'username'); // Assuming 'id' is the foreign key in 'keys' table and 'username' is the field you want to fetch from 'users' table.
        }])->get();
    }
    public function create(Request $request)
    {
        $validated = $request->validate([
            'key' => "required",
            'userId' => "required",
        ]);

        $key = new Key();
        $key->key = $validated['key'];
        $key->user_id = $validated['userId'];
        $key->save();

        // Load user relationship after saving
        $key->load('user');

        return response()->json(['message' => "Key successfully added", 'key' => $key]);
    }

    public function destroy($id)
    {
        $key = Key::find($id);

        if ($key) {
            $key->delete();

            return response()->json(['message' => "Key successfully deleted"]);
        } else {
            return response()->json(['message' => "Key not found"], 404);
        }
    }
}
