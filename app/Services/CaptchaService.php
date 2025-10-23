<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaService
{
    /**
     * Generate a new CAPTCHA challenge.
     * 
     * @return array
     */
    public static function generate(): array
    {
        // Generate a unique token
        $token = Str::random(32);
        
        // Generate random numbers for the math problem
        $num1 = rand(1, 20);
        $num2 = rand(1, 20);
        
        // Randomly choose operation (addition or subtraction)
        $operation = rand(0, 1) === 0 ? '+' : '-';
        
        // Calculate the correct answer
        $answer = $operation === '+' ? $num1 + $num2 : $num1 - $num2;
        
        // For subtraction, ensure result is positive
        if ($operation === '-' && $answer < 0) {
            // Swap numbers if result would be negative
            [$num1, $num2] = [$num2, $num1];
            $answer = $num1 - $num2;
        }
        
        // Store the answer in cache with the token (expires in 5 minutes)
        Cache::put("captcha:{$token}", $answer, now()->addMinutes(5));
        
        // Return the challenge
        return [
            'token' => $token,
            'challenge' => "{$num1} {$operation} {$num2}",
            'expires_in' => 300, // 5 minutes in seconds
        ];
    }
    
    /**
     * Verify a CAPTCHA response.
     * 
     * @param string $token
     * @param int $userAnswer
     * @return bool
     */
    public static function verify(string $token, $userAnswer): bool
    {
        // Get the correct answer from cache
        $correctAnswer = Cache::get("captcha:{$token}");
        
        // If token doesn't exist or has expired
        if ($correctAnswer === null) {
            return false;
        }
        
        // Delete the token (one-time use)
        Cache::forget("captcha:{$token}");
        
        // Verify the answer
        return (int) $userAnswer === (int) $correctAnswer;
    }
    
    /**
     * Check if a token exists and is valid.
     * 
     * @param string $token
     * @return bool
     */
    public static function tokenExists(string $token): bool
    {
        return Cache::has("captcha:{$token}");
    }
}
