<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeaveVerifierMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        abort_unless($request->user()->canVerifyLeaves(), 403, __('ui.unauthorized_access'));

        return $next($request);
    }
}
