<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\LogRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //пропускаем логи, чтобы не было рекурсии
        if ($request->is('logs-requests*')) {
            return $next($request);
        }

        $start = microtime(true);
        $response = $next($request);
        $end = microtime(true);

        // Удаляем старые логи (старше 73 часов)
        LogRequest::where('created_at', '<', now()->subHours(73))->delete();

        // Определяем контроллер и метод
        $route = $request->route();
        $controllerPath = null;
        $controllerMethod = null;
        if ($route) {
            $action = $route->getAction('controller') ?? null;
            if (is_string($action) && strpos($action, '@') !== false) {
                [$controllerPath, $controllerMethod] = explode('@', $action);
            }
        }

        // Exclude logging request body for file uploads
        $requestBody = null;
        if (!$request->isMethod('POST') || !$request->is('api/photos')) {
             $requestBody = $request->getContent();
        }

        // Сохраняем лог
        LogRequest::create([
            'full_url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'controller_path' => $controllerPath,
            'controller_method' => $controllerMethod,
            'request_body' => $requestBody,
            'request_headers' => json_encode($request->headers->all()),
            'user_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'response_status' => $response->getStatusCode(),
            'response_body' => method_exists($response, 'getContent') ? $response->getContent() : null,
            'response_headers' => json_encode($response->headers->all()),
            'called_at' => now(),
        ]);

        return $response;
    }
}
