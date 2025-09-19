<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class RateLimitingService
{
    private string $cacheDir;
    private int $maxRequests;
    private int $timeWindow;
    private array $whitelist;

    public function __construct(string $cacheDir, int $maxRequests = 5, int $timeWindow = 600, string $whitelist = '')
    {
        $this->cacheDir = $cacheDir;
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
        $this->whitelist = $this->parseWhitelist($whitelist);
    }

    /**
     * Parse comma-separated whitelist string into array
     */
    private function parseWhitelist(string $whitelist): array
    {
        if (empty($whitelist)) {
            return [];
        }

        return array_map('trim', explode(',', $whitelist));
    }

    /**
     * Check if the IP address is within rate limits
     */
    public function isAllowed(string $ip): bool
    {
        $rateLimitFile = $this->getRateLimitFilePath($ip);
        
        if (!file_exists($rateLimitFile)) {
            return true;
        }

        $data = $this->readRateLimitData($rateLimitFile);
        $now = time();

        // Clean old requests outside the time window
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });

        // Check if we're under the limit
        if (count($data) >= $this->maxRequests) {
            return false;
        }

        return true;
    }

    /**
     * Record a request for the given IP
     */
    public function recordRequest(string $ip): void
    {
        $rateLimitFile = $this->getRateLimitFilePath($ip);
        $data = [];

        if (file_exists($rateLimitFile)) {
            $data = $this->readRateLimitData($rateLimitFile);
        }

        $now = time();
        
        // Clean old requests outside the time window
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });

        // Add current request
        $data[] = $now;

        $this->writeRateLimitData($rateLimitFile, $data);
    }

    /**
     * Get remaining requests for the IP
     */
    public function getRemainingRequests(string $ip): int
    {
        $rateLimitFile = $this->getRateLimitFilePath($ip);
        
        if (!file_exists($rateLimitFile)) {
            return $this->maxRequests;
        }

        $data = $this->readRateLimitData($rateLimitFile);
        $now = time();

        // Clean old requests outside the time window
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });

        return max(0, $this->maxRequests - count($data));
    }

    /**
     * Get time until next request is allowed (in seconds)
     */
    public function getTimeUntilReset(string $ip): int
    {
        $rateLimitFile = $this->getRateLimitFilePath($ip);
        
        if (!file_exists($rateLimitFile)) {
            return 0;
        }

        $data = $this->readRateLimitData($rateLimitFile);
        $now = time();

        // Clean old requests outside the time window
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });

        if (count($data) < $this->maxRequests) {
            return 0;
        }

        // Find the oldest request in the current window
        $oldestRequest = min($data);
        return ($oldestRequest + $this->timeWindow) - $now;
    }

    /**
     * Extract IP address from request, handling proxies
     */
    public function getClientIp(Request $request): string
    {
        // Check for IP in X-Forwarded-For header (for reverse proxies)
        $forwardedFor = $request->headers->get('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        // Check for IP in X-Real-IP header
        $realIp = $request->headers->get('X-Real-IP');
        if ($realIp && filter_var($realIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $realIp;
        }

        // Fall back to direct connection IP
        return $request->getClientIp();
    }

    /**
     * Check if IP is whitelisted (for testing)
     */
    public function isWhitelisted(string $ip): bool
    {
        return in_array($ip, $this->whitelist);
    }

    /**
     * Get the file path for rate limit data
     */
    private function getRateLimitFilePath(string $ip): string
    {
        // Sanitize IP for filename
        $safeIp = preg_replace('/[^a-zA-Z0-9._-]/', '_', $ip);
        return $this->cacheDir . '/rate_limit_' . $safeIp . '.json';
    }

    /**
     * Read rate limit data from file
     */
    private function readRateLimitData(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        return is_array($data) ? $data : [];
    }

    /**
     * Write rate limit data to file
     */
    private function writeRateLimitData(string $filePath, array $data): void
    {
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        file_put_contents($filePath, json_encode($data));
    }
}
