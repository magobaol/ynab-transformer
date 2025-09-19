<?php

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\RateLimitingService;
use Symfony\Component\HttpFoundation\Request;

class RateLimitingServiceTest extends TestCase
{
    private RateLimitingService $service;
    private string $testCacheDir;

    protected function setUp(): void
    {
        $this->testCacheDir = sys_get_temp_dir() . '/rate_limit_test_' . uniqid();
        $this->service = new RateLimitingService($this->testCacheDir, 3, 60, ''); // 3 requests per 60 seconds for testing
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testCacheDir)) {
            $files = glob($this->testCacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testCacheDir);
        }
    }

    public function test_isAllowed_with_no_previous_requests_returns_true()
    {
        $result = $this->service->isAllowed('192.168.1.1');
        $this->assertTrue($result);
    }

    public function test_isAllowed_with_requests_under_limit_returns_true()
    {
        $ip = '192.168.1.1';
        
        // Record 2 requests (under limit of 3)
        $this->service->recordRequest($ip);
        $this->service->recordRequest($ip);
        
        $result = $this->service->isAllowed($ip);
        $this->assertTrue($result);
    }

    public function test_isAllowed_with_requests_at_limit_returns_false()
    {
        $ip = '192.168.1.1';
        
        // Record 3 requests (at limit)
        $this->service->recordRequest($ip);
        $this->service->recordRequest($ip);
        $this->service->recordRequest($ip);
        
        $result = $this->service->isAllowed($ip);
        $this->assertFalse($result);
    }

    public function test_recordRequest_increments_request_count()
    {
        $ip = '192.168.1.1';
        
        $this->service->recordRequest($ip);
        $remaining = $this->service->getRemainingRequests($ip);
        
        $this->assertEquals(2, $remaining); // 3 max - 1 recorded = 2 remaining
    }

    public function test_getRemainingRequests_returns_correct_count()
    {
        $ip = '192.168.1.1';
        
        // No requests yet
        $remaining = $this->service->getRemainingRequests($ip);
        $this->assertEquals(3, $remaining);
        
        // After 2 requests
        $this->service->recordRequest($ip);
        $this->service->recordRequest($ip);
        $remaining = $this->service->getRemainingRequests($ip);
        $this->assertEquals(1, $remaining);
        
        // After 3 requests (at limit)
        $this->service->recordRequest($ip);
        $remaining = $this->service->getRemainingRequests($ip);
        $this->assertEquals(0, $remaining);
    }

    public function test_getTimeUntilReset_returns_zero_when_under_limit()
    {
        $ip = '192.168.1.1';
        
        $this->service->recordRequest($ip);
        $timeUntilReset = $this->service->getTimeUntilReset($ip);
        
        $this->assertEquals(0, $timeUntilReset);
    }

    public function test_getTimeUntilReset_returns_positive_when_at_limit()
    {
        $ip = '192.168.1.1';
        
        // Record 3 requests to reach limit
        $this->service->recordRequest($ip);
        $this->service->recordRequest($ip);
        $this->service->recordRequest($ip);
        
        $timeUntilReset = $this->service->getTimeUntilReset($ip);
        
        $this->assertGreaterThan(0, $timeUntilReset);
        $this->assertLessThanOrEqual(60, $timeUntilReset); // Should be within time window
    }

    public function test_getClientIp_extracts_ip_from_x_forwarded_for()
    {
        $request = Request::create('/test');
        $request->headers->set('X-Forwarded-For', '203.0.113.1, 192.168.1.1');
        
        $ip = $this->service->getClientIp($request);
        
        $this->assertEquals('203.0.113.1', $ip);
    }

    public function test_getClientIp_extracts_ip_from_x_real_ip()
    {
        $request = Request::create('/test');
        $request->headers->set('X-Real-IP', '203.0.113.1');
        
        $ip = $this->service->getClientIp($request);
        
        $this->assertEquals('203.0.113.1', $ip);
    }

    public function test_getClientIp_falls_back_to_direct_ip()
    {
        $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        
        $ip = $this->service->getClientIp($request);
        
        $this->assertEquals('192.168.1.1', $ip);
    }

    public function test_isWhitelisted_returns_false_when_no_whitelist_file()
    {
        $result = $this->service->isWhitelisted('192.168.1.1');
        $this->assertFalse($result);
    }

    public function test_isWhitelisted_returns_true_for_whitelisted_ip()
    {
        // Create service with whitelist
        $whitelistService = new RateLimitingService($this->testCacheDir, 3, 60, '192.168.1.1, 203.0.113.1');
        
        $result = $whitelistService->isWhitelisted('192.168.1.1');
        $this->assertTrue($result);
        
        $result = $whitelistService->isWhitelisted('203.0.113.1');
        $this->assertTrue($result);
        
        $result = $whitelistService->isWhitelisted('192.168.1.2');
        $this->assertFalse($result);
    }

    public function test_different_ips_have_separate_rate_limits()
    {
        $ip1 = '192.168.1.1';
        $ip2 = '192.168.1.2';
        
        // IP1 reaches limit
        $this->service->recordRequest($ip1);
        $this->service->recordRequest($ip1);
        $this->service->recordRequest($ip1);
        
        // IP2 should still be allowed
        $this->assertTrue($this->service->isAllowed($ip2));
        $this->assertEquals(3, $this->service->getRemainingRequests($ip2));
        
        // IP1 should be blocked
        $this->assertFalse($this->service->isAllowed($ip1));
        $this->assertEquals(0, $this->service->getRemainingRequests($ip1));
    }
}
