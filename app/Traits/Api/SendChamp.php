<?php

namespace App\Traits\Api;

use App\Interface\OtpServiceInterface;
use Illuminate\Support\Facades\Log;

class SendChamp implements OtpServiceInterface
{
    public function sendOtp(string $phoneNumber): array
    {
        $curl = curl_init();
        $payload = [
            'channel' => 'sms',
            'sender' => 'SAlert',
            'token_type' => 'numeric',
            'token_length' => '6',
            'expiration_time' => 5,
            'customer_mobile_number' => $phoneNumber,
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.sendchamp.com/api/v1/verification/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.config('externallinks.sendchamp_api_url'),
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['status' => 'failed', 'error' => $error];
        }

        $otp = json_decode($response, true);

        Log::info('SendChamp OTP response:', ['response' => $response]);

        return [
            'status' => $otp['status'] === 'success' ? true : false,
            'otp' => $otp['data']['token'] ?? null,
            'message' => $otp['message'] ?? 'Unknown error',
        ];
    }
}
