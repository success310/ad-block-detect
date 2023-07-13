<?php

namespace App\Services;

use Carbon\Carbon;
use CyrildeWit\EloquentViewable\View;
use Illuminate\Support\Facades\DB;

class StatService
{
    private function getQuery()
    {
        return DB::table('users')
            ->join('pastes', 'pastes.user_id', '=', 'users.id')
            ->join('views', 'views.viewable_id', '=', 'pastes.id');
    }
    public function getTodayViews($user = null)
    {
        if (!$user)
            $user = auth()->user();
        $query = $this->getQuery();
        $query = $query->whereDate('viewed_at', Carbon::today());
        if (!$user->isSuperAdmin())
            $query = $query->where('users.id', '=', $user->id);
        // View::whereDate('viewed_at', Carbon::today())->count();
        return $query->count();
    }
    public function getWeekViews($user = null)
    {
        if (!$user)
            $user = auth()->user();
        $query = $this->getQuery();
        $query = $query->whereBetween('viewed_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        if (!$user->isSuperAdmin())
            $query = $query->where('users.id', '=', $user->id);
        // View::whereDate('viewed_at', Carbon::today())->count();
        return $query->count();
    }
    public function getTotalViews($user = null, $startDate = null, $endDate = null)
    {
        if (!$user)
            $user = auth()->user();
        $query = $this->getQuery();
        if (!$user->isSuperAdmin())
            $query = $query->where('users.id', '=', $user->id);

        if ($startDate && $endDate) {
            $query = $query->whereDate('viewed_at', '>=', $startDate)->whereDate('viewed_at', '<=', $endDate);
        }
        return $query->count();
    }
}
