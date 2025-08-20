<?php

namespace App\Traits\Api;

class Termii
{
    public function sendOtp($phoneNumber, $otp)
    {
        $curl = curl_init();

        $data = [
            'api_key' => config('externallinks.termii_api.key'),
            'message_type' => 'NUMERIC',
            'to' => $phoneNumber,
            'from' => 'Approved Sender ID or Configuration ID',
            'channel' => 'dnd',
            'pin_attempts' => 4,
            'pin_time_to_live' => 5,
            'pin_length' => 4,
            'pin_placeholder' => "< {$otp->token} >",
            'message_text' => "Your pin is < {$otp->token} >",
            'pin_type' => 'NUMERIC',
        ];

        $post_data = json_encode($data);

        curl_setopt_array($curl, [
            CURLOPT_URL => config('externallinks.termii_api.key').'/api/sms/otp/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo 'cURL Error #:'.$err;
        } else {
            $otp = json_decode($response);

            $res = [
                'verification_code' => $otp,
                'phone' => $phoneNumber,
                'api_response' => $otp->message,
                'status' => property_exists($otp, 'status') ? $otp->status : null,
                'pending_response' => json_encode($otp),
                'api_provider' => 'tizeti',
                'state' => 'pending',
            ];

            return $res;
        }
    }

    public function verifyOtp($reference, $pin)
    {
        $curl = curl_init();

        $data = [
            'api_key' => config('externallinks.termii_api.key'),
            'pin_id' => $reference,
            'pin' => $pin,
        ];

        $post_data = json_encode($data);

        curl_setopt_array($curl, [
            CURLOPT_URL => config('externallinks.termii_api.key').'/api/sms/otp/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return 'cURL Error #:'.$err;
        } else {
            return json_decode($response);
        }
    }
}
