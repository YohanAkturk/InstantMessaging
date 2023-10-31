<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ChatReplay
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
        $cur = time();
        $time = $request->header('timestamp');
        if($time == null || abs($cur - $time) > 30) {
            abort(403);
        }
        
        return $next($request);
    }
}
