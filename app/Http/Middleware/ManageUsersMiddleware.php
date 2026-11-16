<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageUsersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        abort_unless($request->user()->canManageUsers(), 403, __('ui.unauthorized_access'));

        return $next($request);
    }
}
