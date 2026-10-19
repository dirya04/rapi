<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $roleName = $user?->role?->role_name;

        if (!$user || !$roleName || !in_array($roleName, $roles, true)) {
            return response()->json(['message' => 'Akses tidak diizinkan.'], 403);
        }

        return $next($request);
    }
}
