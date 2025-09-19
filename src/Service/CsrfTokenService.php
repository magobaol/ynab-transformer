<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class CsrfTokenService
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Generate a new CSRF token
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->storeToken($token);
        return $token;
    }

    /**
     * Get the current CSRF token
     */
    public function getToken(): string
    {
        $token = $this->getStoredToken();
        
        if (!$token) {
            $token = $this->generateToken();
        }
        
        return $token;
    }

    /**
     * Validate a CSRF token
     */
    public function validateToken(string $token): bool
    {
        $storedToken = $this->getStoredToken();
        
        if (!$storedToken) {
            return false;
        }
        
        return hash_equals($storedToken, $token);
    }

    /**
     * Validate CSRF token from request
     */
    public function validateTokenFromRequest(Request $request): bool
    {
        $token = $request->request->get('_token') ?? $request->headers->get('X-CSRF-Token');
        
        if (!$token) {
            return false;
        }
        
        return $this->validateToken($token);
    }

    /**
     * Store token in file
     */
    private function storeToken(string $token): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        $tokenFile = $this->cacheDir . '/csrf_token.txt';
        file_put_contents($tokenFile, $token);
    }

    /**
     * Get stored token from file
     */
    private function getStoredToken(): ?string
    {
        $tokenFile = $this->cacheDir . '/csrf_token.txt';
        
        if (!file_exists($tokenFile)) {
            return null;
        }
        
        $token = file_get_contents($tokenFile);
        return $token ?: null;
    }
}
