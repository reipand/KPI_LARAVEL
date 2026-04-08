<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HrMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('user');
        
        if (!$user || !$user->isHR()) {
            return redirect('/dashboard')->with('error', 'Akses ditolak');
        }
        
        return $next($request);
    }
}
