<?php

namespace App\Services;

use App\Http\Resources\PayoutResource;
use App\Models\User;
use App\Models\Payout;

class PayoutService
{
    public function getPayouts(User $user, int $searchUserId = null)
    {
        if ($user->isAdmin) {
            $query = Payout::with('user'); // eager load the user

            if (!is_null($searchUserId)) {
                $query->where('user_id', $searchUserId);
            }

            $payouts = $query->orderBy('created_at', 'desc')->get();
        } else {
            $payouts = $user->payouts()->with('user')->orderBy('created_at', 'desc')->get(); // eager load the user
        }

        // Return as a resource collection
        return PayoutResource::collection($payouts);
    }
}
