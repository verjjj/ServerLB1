<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TwoFactorAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
// с временным токеном мы можем получить инфомацию о пользователе. так быть не должно.

class TwoFactorAuthController extends Controller
{
    public function getStatus(Request $request)
    {
        $user = Auth::user();
        $status = $user->twoFactorAuth ? $user->twoFactorAuth->is_enabled : false;
        return response()->json(['is_enabled' => $status]);
    }

    public function requestCode(Request $request)
    {
        $user = Auth::user();
        $clientIdentifier = $request->ip() . '_' . $request->header('User-Agent');

        $globalAttempts = Cache::get("2fa_global_attempts_{$user->id}", 0);
        if ($globalAttempts >= 5) {
            return response()->json(['error' => 'Too many attempts. Please wait 50 seconds.'], 429);
        }

        $clientAttempts = Cache::get("2fa_client_attempts_{$user->id}_{$clientIdentifier}", 0);
        if ($clientAttempts >= 3) {
            return response()->json(['error' => 'Too many attempts from this device. Please wait 30 seconds.'], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addSeconds((int) config('services.two_factor.code_expiration', 300));

        $user->twoFactorAuth()->updateOrCreate(
            ['client_identifier' => $clientIdentifier],
            [
                'code' => $code,
                'code_expires_at' => $expiresAt,
                'is_enabled' => $user->twoFactorAuth ? $user->twoFactorAuth->is_enabled : false
            ]
        );

        Cache::put("2fa_global_attempts_{$user->id}", $globalAttempts + 1, now()->addMinutes(5));
        Cache::put("2fa_client_attempts_{$user->id}_{$clientIdentifier}", $clientAttempts + 1, now()->addMinutes(5));

        return response()->json([
            'message' => 'Verification code sent',
            'code' => $code,
            'expires_in' => config('services.two_factor.code_expiration', 300)
        ]);
    }


    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $clientIdentifier = $request->ip() . '_' . $request->header('User-Agent');

        $twoFactorAuth = $user->twoFactorAuth()
            ->where('client_identifier', $clientIdentifier)
            ->where('code', $request->code)
            ->first();

        if (!$twoFactorAuth || $twoFactorAuth->code_expires_at < now()) {
            return response()->json(['error' => 'Invalid or expired code'], 400);
        }

        $twoFactorAuth->update(['code' => null]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => '2FA verification successful',
            'token' => $token
        ]);
    }

    public function toggleTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'enable' => 'required|boolean',
            'code' => 'nullable|required_if:enable,false|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid password'], 400);
        }

        if (!$request->enable) {
            $clientIdentifier = $request->ip() . '_' . $request->header('User-Agent');
            $twoFactorAuth = $user->twoFactorAuth()
                ->where('client_identifier', $clientIdentifier)
                ->where('code', $request->code)
                ->first();

            if (!$twoFactorAuth || $twoFactorAuth->code_expires_at < now()) {
                return response()->json(['error' => 'Invalid or expired code'], 400);
            }
        }

        $user->twoFactorAuth()->updateOrCreate(
            [],
            ['is_enabled' => $request->enable]
        );

        return response()->json([
            'message' => '2FA ' . ($request->enable ? 'enabled' : 'disabled'),
            'is_enabled' => $request->enable
        ]);
    }
}
