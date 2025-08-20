<?php

namespace App\Traits\Api;

class WifiCall
{
    public function sendOtp($phoneNumber, $otp)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.tizeti.com/developers/v1/wificall/otp',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'phoneNumber='.$phoneNumber.'&code='.$otp,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer '.config('externallinks.wificall_api'),
                'Content-Type: application/x-www-form-urlencoded',
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

    public function verifyOtp($reference, $otp)
    {

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.tizeti.com/developers/v1/wificall/verifyLoginCode?call_id='.$reference,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'code='.$otp,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Encoding: gzip, deflate',
                'Authorization: Bearer '.config('externallinks.wificall_api'),
                'Content-Type: application/x-www-form-urlencoded',
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
