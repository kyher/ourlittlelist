<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseAdminSessionCookie
{
    /**
     * Give admin pages a session of their own, so the two logins stay independent in one browser.
     *
     * A separate guard is not enough: Fortify's logout invalidates the whole session, which
     * would also sign out an admin sharing it. This must run before StartSession.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookie = config('session.cookie');

        app('session.store')->setName($request->is('admin', 'admin/*') ? $cookie.'-admin' : $cookie);

        return $next($request);
    }
}
