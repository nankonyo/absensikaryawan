<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $allowed = [];
        foreach ($roles as $role) {
            $allowed = array_merge($allowed, array_filter(array_map('trim', explode(',', $role))));
        }

        $user = auth()->user();

        if (! in_array($user->role?->name, $allowed, true)) {
            abort(403, __('ui.unauthorized_access'));
        }

        return $next($request);
    }
}
