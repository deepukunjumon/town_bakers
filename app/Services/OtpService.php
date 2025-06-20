<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class OtpService
{
    /**
     * Generate a new OTP.
     *
     * @param int $length
     * @return string
     */
    public function generate(int $length = 6): string
    {
        // Generate a random numeric OTP
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Store the OTP in the cache for a given identifier.
     *
     * @param string $identifier
     * @param string $otp
     * @param int $minutes
     * @return void
     */
    public function store(string $identifier, string $otp, int $minutes = 10): void
    {
        Cache::put($this->getCacheKey($identifier), $otp, now()->addMinutes($minutes));
    }

    /**
     * Validate the given OTP for an identifier.
     *
     * @param string $identifier
     * @param string $otp
     * @return bool
     */
    public function validate(string $identifier, string $otp): bool
    {
        $storedOtp = Cache::get($this->getCacheKey($identifier));

        if ($storedOtp && $storedOtp === $otp) {
            return true;
        }

        return false;
    }

    /**
     * Clear the OTP from the cache.
     *
     * @param string $identifier
     * @return void
     */
    public function clear(string $identifier): void
    {
        Cache::forget($this->getCacheKey($identifier));
    }

    /**
     * Get the cache key for the identifier.
     *
     * @param string $identifier
     * @return string
     */
    private function getCacheKey(string $identifier): string
    {
        return 'otp_' . $identifier;
    }

    /**
     * Generate a short-lived password reset token after OTP verification.
     *
     * @param string $identifier
     * @param int $minutes
     * @return string
     */
    public function generateResetToken(string $identifier, int $minutes = 10): string
    {
        $token = bin2hex(random_bytes(32));
        Cache::put($this->getResetTokenCacheKey($identifier), $token, now()->addMinutes($minutes));
        return $token;
    }

    /**
     * Validate the password reset token for an identifier.
     *
     * @param string $identifier
     * @param string $token
     * @return bool
     */
    public function validateResetToken(string $identifier, string $token): bool
    {
        $storedToken = Cache::get($this->getResetTokenCacheKey($identifier));
        return $storedToken && hash_equals($storedToken, $token);
    }

    /**
     * Clear the password reset token from the cache.
     *
     * @param string $identifier
     * @return void
     */
    public function clearResetToken(string $identifier): void
    {
        Cache::forget($this->getResetTokenCacheKey($identifier));
    }

    /**
     * Get the cache key for the reset token.
     *
     * @param string $identifier
     * @return string
     */
    private function getResetTokenCacheKey(string $identifier): string
    {
        return 'reset_token_' . $identifier;
    }
}
