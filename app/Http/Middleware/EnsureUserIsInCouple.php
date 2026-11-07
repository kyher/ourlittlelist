<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsInCouple
{
    /**
     * Reviews belong to a couple, so single users are sent to find their partner (FR-001).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isInCouple()) {
            return redirect()->route('partner.search')
                ->with('status', __('Connect with your partner to start reviewing.'));
        }

        return $next($request);
    }
}
