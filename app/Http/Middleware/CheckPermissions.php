<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissions
{
    public function handle(Request $request, Closure $next, string $permission, $permissionCode): Response
    {
        if (!$request->user()->hasPermission($permission)) {
            return response()->json([
                 'message' => "Permission denied: You do not have the required permission to perform this action.",
                'required_permission' => $permissionCode,

            ], 403);
        }

        return $next($request);


    }

}
