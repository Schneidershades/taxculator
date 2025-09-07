<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

/**
 * @group Auth
 * Email verification: send a verification link and confirm via signed URL.
 */
class EmailVerificationController extends Controller
{
    public function send(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return $this->respondSuccess(['message' => 'Email already verified.']);
        }
        $user->sendEmailVerificationNotification();
        return $this->respondSuccess(['message' => 'Verification link sent.']);
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        if (! hash_equals((string) $hash, sha1((string) $user->getEmailForVerification()))) {
            return $this->respondError('Invalid verification link.', 400);
        }
        if ($user->hasVerifiedEmail()) {
            return $this->respondSuccess(['message' => 'Email already verified.']);
        }
        $user->markEmailAsVerified();
        event(new Verified($user));
        return $this->respondSuccess(['message' => 'Email verified successfully.']);
    }
}
