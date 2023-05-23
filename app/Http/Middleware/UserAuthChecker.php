<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UserAuthChecker
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request Request
     * @param \Closure                 $next    Next Function
     * 
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::guard("users")->check() && !Auth::guard("firm_users")->check() && request()->path() != "/login'") {
            $redirect_to = substr(request()->path(), 0, 4) == "api/" ? "" : $request->fullUrl();
            return redirect('/login')
                ->with("msg-type", "warning")
                ->with("msg", "Access Denied! Please Login First")
                ->with("redirect_to", $redirect_to);
        }
        return $next($request);
    }
}
