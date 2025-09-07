<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Auth
 * Two-factor authentication (2FA) setup and verification.
 */
class TwoFactorController extends Controller
{
    public function setup(Request $request)
    {
        $user = $request->user();
        $g2fa = app(\PragmaRX\Google2FALaravel\Google2FA::class);
        $secret = $g2fa->generateSecretKey();
        $issuer = config('app.name', 'TaxPilot');
        $email = $user->email ?? 'user';
        $otpauth = $g2fa->getQRCodeUrl($issuer, $email, $secret);

        // store secret temporarily until verified
        $user->two_factor_secret = $secret;
        $user->save();

        return $this->respondSuccess(['message' => '2FA secret generated.', 'data' => [
            'secret' => $secret,
            'otpauth_url' => $otpauth,
        ]]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);
        $user = $request->user();
        $g2fa = app(\PragmaRX\Google2FALaravel\Google2FA::class);

        if (empty($user->two_factor_secret)) {
            return $this->respondError('2FA not initialized.', 422);
        }

        $ok = $g2fa->verifyKey($user->two_factor_secret, $data['otp']);
        if (!$ok) {
            return $this->respondError('Invalid code.', 422);
        }

        // generate recovery codes
        $codes = collect(range(1, 8))->map(fn() => Str::random(10).'-'.Str::random(10))->all();
        $user->two_factor_recovery_codes = json_encode($codes);
        $user->save();

        return $this->respondSuccess(['message' => '2FA enabled.', 'data' => ['recovery_codes' => $codes]]);
    }

    public function downloadRecoveryCodes(Request $request)
    {
        $user = $request->user();
        $codes = json_decode($user->two_factor_recovery_codes ?? '[]', true) ?: [];
        $content = implode("\n", $codes);
        $filename = 'two_factor_backup_codes.txt';
        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
