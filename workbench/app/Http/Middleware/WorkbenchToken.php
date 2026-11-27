<?php

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Workbench\App\Models\User;

/**
 * Static bearer tokens. "user-token" is a regular user, "admin-token" is the admin.
 * A user already authenticated, for example through Nimbus impersonation, is accepted too.
 */
class WorkbenchToken
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $user = match ($request->bearerToken()) {
            'user-token' => User::where('role', 'user')->first(),
            'admin-token' => User::where('role', 'admin')->first(),
            default => null,
        };

        // A user set by Nimbus impersonation counts as authenticated.
        $user ??= Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($role && $user->role !== $role) {
            return response()->json(['message' => 'This action requires the '.$role.' role.'], 403);
        }

        Auth::setUser($user);

        return $next($request);
    }
}
