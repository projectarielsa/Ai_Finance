<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'password',
        'otp_code', 'otp_expires_at', 'attempts',
    ];

    protected $hidden = ['password', 'otp_code'];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
        ];
    }

    /** Generate OTP 6 digit, berlaku 10 menit */
    public function generateOtp(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'otp_code'       => $code,
            'otp_expires_at' => now()->addMinutes(10),
            'attempts'       => 0,
        ]);
        return $code;
    }

    /** Cek apakah OTP masih valid */
    public function isOtpValid(string $code): bool
    {
        return $this->otp_code === $code
            && $this->otp_expires_at
            && $this->otp_expires_at->isFuture();
    }

    /** Cek apakah sudah terlalu banyak percobaan salah */
    public function hasExceededAttempts(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }
}
