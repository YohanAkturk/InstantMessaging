<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthReplay
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
        if($request->route()->uri() == 'logout') {
            return $next($request);
        }

        if($request->route()->methods()[0] == 'GET' && $request->route()->uri() == 'login') {
            return $next($request);
        }

        if($request->route()->methods()[0] == 'GET' && $request->route()->uri() == 'register') {
            return $next($request);
        }

        $cur = time();
        $time = $request->input('timestamp');
        if($time == null || $cur - $time > 60) {
            abort(403);
        }

        return $next($request);
    }
}
