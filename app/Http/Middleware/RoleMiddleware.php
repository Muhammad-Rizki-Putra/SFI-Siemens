<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $roles = array_map('trim', explode(',', $role));

        if (!Auth::check() || !in_array(Auth::user()->role, $roles, true)) {
            return redirect()->route('ideas.index')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}