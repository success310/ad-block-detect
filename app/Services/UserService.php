<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function getAll()
    {
        $users = User::where('isAdmin', false)->get(['id', 'name','allowAffiliate']);
        $users->prepend(['id' => 'guest', 'name' => 'Guest']);
        $users->prepend(['id' => 'all', 'name' => 'All users']);
        return $users;
    }
    public function findUserByField($field, $value)
    {
        return User::with(['pastes' => function ($q) {
            return $q->latest();
        }])->where('isAdmin', false)->where($field, $value)->first();
    }
    public function generateUniqueUsername($name)
    {
        // Remove special characters and spaces, and convert the name to lowercase
        $username = strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $name));

        // Check if the generated username already exists
        $user = User::where('username', $username)->first();
        $counter = 1;

        // If the username exists, append a number to it and increment the number until a unique username is found
        while ($user) {
            $new_username = $username . $counter;
            $user = User::where('username', $new_username)->first();
            $counter++;
        }

        // If a unique username was found, return it
        if (isset($new_username)) {
            return $new_username;
        }

        // If the generated username is unique, return it
        return $username;
    }
}
