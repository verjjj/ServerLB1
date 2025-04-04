<?php

namespace App\Http\Controllers;

use App\DTO\LoginDTO;
use App\DTO\RegisterDTO;
use App\Enums\TokenAbility;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    // Регистрация нового пользователя
    public function register(RegisterRequest $request): UserResource|JsonResponse
    {
        try {
            $validated = $request->validated();

            // Создание DTO для регистрации
            $dto = new RegisterDTO(
                $validated['username'],
                $validated['email'],
                Hash::make($validated['password']),
                $validated['birthday']
            );

            // Создание пользователя
            $user = User::create([
                'username' => $dto->username,
                'email' => $dto->email,
                'password' => $dto->password,
                'birthday' => $dto->birthday,
            ]);

            return new UserResource($user);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка регистрации',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // Авторизация пользователя
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Создание DTO для логина
            $dto = new LoginDTO(
                $validated['username'],
                $validated['password']
            );

            // Ищем пользователя
            $user = User::query()->where('username', $dto->username)->first();
            if (!$user || !Hash::check($dto->password, $user->password)) {
                return response()->json([
                    'error' => 'Ошибка:(',
                    'message' => 'Неверно указано имя или пароль',
                ], 401);
            }

            // Проверяем количество токенов
            $maxTokens = env('MAX_TOKENS', 10);
            if ($user->tokens()->count() >= $maxTokens) {
                return response()->json([
                    'error' => 'О нет... Лимит токенов превышен...',
                    'message' => 'Превышено максимальное количество активных токенов',
                ], 403);
            }

            $accessToken = $user->createToken(
                'access_token',
                [TokenAbility::ACCESS_API->value], // Способности токена
                now()->addMinutes(config('sanctum.expiration')) // Преобразуем в DateTimeInterface
            );

            $refreshToken = $user->createToken(
                'refresh_token',
                [TokenAbility::ISSUE_ACCESS_TOKEN->value],
                now()->addMinutes(config('sanctum.rt_expiration')) // Преобразуем в DateTimeInterface
            );

            return response()->json([
                'message' => 'Успешный вход',
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken->plainTextToken,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Login failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function refreshToken(Request $request): JsonResponse
    {
        // Получаем refresh-токен из заголовка
        $refreshToken = $request->bearerToken();

        if (!$refreshToken) {
            return response()->json(['error' => 'Отсутствует refresh-токен'], 401);
        }

        // Находим токен
        $token = PersonalAccessToken::findToken($refreshToken);

        if (!$token ||
            $token->expires_at < now() ||
            !$token->can(TokenAbility::ISSUE_ACCESS_TOKEN->value)) {
            return response()->json(['error' => 'Недействительный или просроченный токен'], 401);
        }

        $request->setUserResolver(fn() => $token->tokenable);

        // Создаём новый access-токен
        $newAccessToken = $token->tokenable->createToken(
            'access_token',
            [TokenAbility::ACCESS_API->value], // Способности токена
            now()->addMinutes(config('sanctum.expiration')) // Преобразуем в DateTimeInterface
        );

        return response()->json([
            'access_token' => $newAccessToken->plainTextToken,
        ], 200);
    }


    // Получение информации об авторизованном пользователе
    public function infoUser(Request $request): JsonResponse
    {
        $user = $request->user();

        // Если пользователь не авторизован, возвращаем ошибку
        if (!$user) {
            return response()->json([
                'error' => 'Ошибка:(',
                'message' => 'Пользователь не авторизован',
            ], 401);
        }

        // Возвращаем данные пользователя в формате JSON через ресурс UserResource
        return response()->json(new UserResource($user));
    }

    // Получение списка токенов пользователя
    public function tokens(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Ошибка:)',
                'message' => 'Пользователь не авторизован',
            ], 401);
        }

        /** @var User|null $user */
        $tokens = $user->tokens()->get(['id', 'name', 'created_at']); // Листинг активных токенов

        return response()->json([
            'tokens' => $tokens,
        ]);
    }

    // Выход (удаление текущего токена)
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Ошибка:(',
                'message' => 'Пользователь не найден',
            ], 401);
        }

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Успешно, до свидания!'], 200);
    }

    // Отзыв всех токенов пользователя
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Ошибка:(',
                'message' => 'Пользователь не найден',
            ], 401);
        }

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Все токены пользователя были отозваны',
        ], 200);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Неверный текущий пароль'], 400);
        }

        // Удаляем все старые токены (требуется повторная авторизация)
        $user->tokens()->delete();

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Пароль успешно изменён'], 200);
    }
}
