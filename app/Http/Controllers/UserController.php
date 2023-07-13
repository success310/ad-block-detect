<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasteService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    private $userService, $pasteService;
    public function __construct()
    {

        $this->middleware('auth:sanctum', ['except' => [
            'profil'
        ]]);
        $this->userService = new UserService();
        $this->pasteService = new PasteService();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Gate::allows('is-admin'))
            return response(['success' => false, 'message' => 'unauthorized',], 500);

        return $this->userService->getAll();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $pastes = $this->pasteService->findById($id, $request->search);
        return $pastes->latest()->paginate($request->limit);
    }
    public function profil($username)
    {
        $user = $this->userService->findUserByField('username', $username);
        if (!$user)
            return response(['message' => 'User not found'], 404);

        return response()->json($user->makeHidden(['isAdmin', 'email']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!Gate::allows('is-admin')) {
            return abort(403, 'unauthorized');
        }
        $validated = $request->validate([
            'allowAffiliate' => 'required|boolean',
            'userId' => 'required|exists:users,id',
        ]);
        $user = User::find($validated['userId']);
        $user->allowAffiliate = $validated['allowAffiliate'];
        $user->save();
    }
    public function updateAll(Request $request)
    {
        if (!Gate::allows('is-admin')) {
            return abort(403, 'unauthorized');
        }
        $validated = $request->validate([
            'allowAffiliate' => 'required|boolean',
        ]);
        $users = User::where('isAdmin', false)->get();
        foreach ($users as $user) {
            $user->allowAffiliate = $validated['allowAffiliate'];
            $user->save();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
