<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\User;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PayoutController extends Controller
{
    private $payoutService;
    public function __construct(PayoutService $payoutService)
    {
        $this->middleware('auth:sanctum')->except(['status']);
        $this->middleware('optional.sanctum')->only(['status']);
        $this->payoutService = $payoutService;
    }
    public function index(Request $request)
    {
        $searchUserId = $request->query('user_id');
        $payouts = $this->payoutService->getPayouts(auth()->user(), $searchUserId);
        $isAllDisabledAffiliate = User::where('isAdmin', false)->where('allowAffiliate', false)->count() == 0;
        return response()->json(['payout' => $payouts, 'isAllDisabledAffiliate' => $isAllDisabledAffiliate]);
    }
    public function claim()
    {
        $user = auth()->user();

        if (!$user->allowAffiliate) {
            return abort(403, 'unauthorized');
        }
        $minAmount = 1;
        if ($user->earnings < $minAmount) {
            return response()->json(['message' => 'You need to earn at least $' . $minAmount . ' in order to withdraw'], Response::HTTP_NOT_ACCEPTABLE);
        }
        $payout = new Payout([
            'user_id' => $user->id,
            'amount' =>  $user->earnings,
            'status' => 'Pending',
        ]);
        $payout->save();
        $user->update(['earnings' => 0]);
        return response()->json(['message' => 'Withdrawal request submitted successfully.', 'payout' => $payout]);
    }

    public function updatePayoutEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email'
        ]);
        auth()->user()->update(['paypal_email' => $validated['email']]);
        return response()->json(['message' => 'Paypal email saved successfully']);
    }
    public function markAsPaid(Request $request)
    {
        if (!Gate::allows('is-admin')) {
            return response(['success' => false, 'message' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'id' => 'required|exists:payouts,id'
        ]);
        Payout::find($validated['id'])->update(['status' => 'Paid']);

        return ['message' => 'Payout updated successfully'];
    }
    public function status()
    {
        $user = auth()->user();

        if ($user) {
            return response()->json(['status' => $user->allowAffiliate]);
        }

        $allowAffiliateUsersCount = User::where('isAdmin', false)->where('allowAffiliate', true)->count();

        if ($allowAffiliateUsersCount === 0) {
            return response()->json(['status' => false]);
        }

        return response()->json(['status' => true]);
    }

    public function updateBalance(Request $request)
    {
        if (!Gate::allows('is-admin')) {
            return response(['success' => false, 'message' => 'unauthorized'], 403);
        }
        $user = User::find($request->userId);
        if ($user) {
            $user->earnings = $request->balance;
            $user->save();
        }
    }
}
