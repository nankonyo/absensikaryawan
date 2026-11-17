<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = ApiToken::findValid($bearer);

        if (! $token || ! $token->user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->touchUsage();

        $user = $token->user;
        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);

        return $next($request);
    }
}
