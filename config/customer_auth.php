<?php

return [
    'otp_expires_minutes' => 10,
    'otp_max_attempts' => 5,
    'display_otp' => env('CUSTOMER_OTP_DISPLAY', true),
    'send_email' => env('CUSTOMER_OTP_SEND_EMAIL', false),
];
