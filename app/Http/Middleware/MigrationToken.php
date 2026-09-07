<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MigrationToken
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $token = config('services.migration.token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Migration token is not configured.',
            ], 500);
        }

        /*
         * Get:
         *
         * Authorization: Bearer YOUR_TOKEN
         */
        $providedToken = $request->bearerToken();

        if (!$providedToken || !hash_equals($token, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised.',
            ], 401);
        }

        return $next($request);
    }
}
