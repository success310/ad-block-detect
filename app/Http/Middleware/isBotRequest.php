<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class isBotRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $API_KEY = 'de10c3651392407e2c420822187659c3';
        if (!$request->header('X-API-KEY') || $request->header('X-API-KEY') != $API_KEY) {
            return response()->json(['error' => 'Unauthorized']);
        }
        return $next($request);
    }
}
