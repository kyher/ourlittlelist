<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasUsername
{
    /**
     * Send signed-in users who have not chosen a username to the choose-username page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('web') && $request->user('web')->username === null) {
            return redirect()->guest(route('username.create'));
        }

        return $next($request);
    }
}
