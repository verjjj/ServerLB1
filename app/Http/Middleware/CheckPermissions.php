<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissions
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()->hasPermission($permission)) {
            return response()->json([
                'message' => 'Forbidden',
                'errors' => [
                    'permission' => ["Required permission: $permission"]
                ]
            ], 403);
        }

        return $next($request);
    }
}
