<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Credentials are fixed. Bearer is "demo-token" and "demo-admin-token", basic is demo and secret.
 */
class AuthController
{
    public function bearer(Request $request): JsonResponse
    {
        if (! in_array($request->bearerToken(), ['demo-token', 'demo-admin-token'], true)) {
            return response()->json(['message' => 'Invalid or missing bearer token.'], 401);
        }

        return response()->json(['message' => 'Authenticated with a bearer token.']);
    }

    public function admin(Request $request): JsonResponse
    {
        return match ($request->bearerToken()) {
            'demo-admin-token' => response()->json(['message' => 'Welcome, admin.']),
            'demo-token' => response()->json(['message' => 'Admins only.'], 403),
            default => response()->json(['message' => 'Invalid or missing bearer token.'], 401),
        };
    }

    public function basic(Request $request): JsonResponse
    {
        if ($request->getUser() !== 'demo' || $request->getPassword() !== 'secret') {
            return response()->json(['message' => 'Invalid credentials.'], 401, ['WWW-Authenticate' => 'Basic']);
        }

        return response()->json(['message' => 'Authenticated with basic auth.']);
    }

    public function session(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Log in with /dev/login-as/{id} first.'], 401);
        }

        return response()->json($request->user()->only('id', 'name', 'email', 'role'));
    }
}
