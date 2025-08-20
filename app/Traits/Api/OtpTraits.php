<?php

namespace App\Traits\Api;

use Ichtrojan\Otp\Otp;

trait OtpTraits
{
    private $length = 4;

    private $validity_period = 10;

    protected function generate_otp($email)
    {
        $otpobj = app(Otp::class);
        $otp = $otpobj->generate($email, 'numeric', (int) $this->length, $this->validity_period);

        return $otp;
    }

    protected function validate_otp($detail)
    {
        $otpobj = app(Otp::class);
        $response = $otpobj->validate($detail['email'], $detail['otp']);

        return $response;
    }
}