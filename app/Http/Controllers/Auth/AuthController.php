<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Auth
 * Authentication endpoints (register, login, logout). Use the returned Sanctum token for authenticated routes.
 */
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $u = User::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return $this->respondSuccess(['message' => 'Account created. Please verify your email.', 'data' => ['id' => $u->id]], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
            'otp' => ['nullable', 'digits:6'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        // If 2FA enabled, require OTP or a valid recovery code
        if (!empty($user->two_factor_secret)) {
            $ok = false;
            if (!empty($data['otp'])) {
                $g2fa = app(\PragmaRX\Google2FALaravel\Google2FA::class);
                $ok = $g2fa->verifyKey($user->two_factor_secret, $data['otp']);
            } elseif (!empty($data['recovery_code']) && !empty($user->two_factor_recovery_codes)) {
                $codes = json_decode($user->two_factor_recovery_codes, true) ?: [];
                $idx = array_search($data['recovery_code'], $codes, true);
                if ($idx !== false) {
                    $ok = true;
                    unset($codes[$idx]);
                    $user->two_factor_recovery_codes = json_encode(array_values($codes));
                    $user->save();
                }
            }
            if (!$ok) {
                throw ValidationException::withMessages(['otp' => ['Two-factor code or recovery code is required.']]);
            }
        }

        $token = $user->createToken($data['device_name'] ?? 'api')->plainTextToken;

        return $this->respondSuccess(['message' => 'Logged in successfully.', 'data' => ['token' => $token]]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }
        return $this->respondSuccess(['message' => 'Logged out.']);
    }
}
