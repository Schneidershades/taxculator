<?php

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('email verification notification can be sent and link verifies user', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => null,
    ]);

    // Login to get token
    $token = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertOk()->json('data.token');

    // Send notification
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/email/verification-notification')
        ->assertOk();

    // Build signed verify URL
    $hash = sha1($user->getEmailForVerification());
    $url = url()->temporarySignedRoute('verification.verify', now()->addMinutes(5), [
        'id' => $user->id,
        'hash' => $hash,
    ]);

    $this->get($url)->assertOk()->assertJsonPath('success', true);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

