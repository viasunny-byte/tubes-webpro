<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\facades\Auth;
use Illuminate\Support\facades\Session;
use Symfony\component\HttpFoundation\Response;

class AuthAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\component\HttpFoundation\Response;) $next
     */
    public function handle(Request $request, Closure $next)
    {
        if(Auth::check())
        {
            if(Auth::user()->utype==='ADM')
            {
                return $next($request);
            }
            else{
                Session::flush();
                return redirect()->route('login');
            }
        }
        else{
            return redirect()->route('login');
        }
    }
}
