<?php

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\CsrfTokenService;
use Symfony\Component\HttpFoundation\Request;

class CsrfTokenServiceTest extends TestCase
{
    private CsrfTokenService $service;
    private string $testCacheDir;

    protected function setUp(): void
    {
        $this->testCacheDir = sys_get_temp_dir() . '/csrf_test_' . uniqid();
        $this->service = new CsrfTokenService($this->testCacheDir);
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

    public function test_generateToken_returns_hex_string()
    {
        $token = $this->service->generateToken();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertTrue(ctype_xdigit($token));
    }

    public function test_generateToken_stores_token_in_file()
    {
        $token = $this->service->generateToken();
        
        $tokenFile = $this->testCacheDir . '/csrf_token.txt';
        $this->assertFileExists($tokenFile);
        $this->assertEquals($token, file_get_contents($tokenFile));
    }

    public function test_getToken_returns_existing_token()
    {
        $originalToken = $this->service->generateToken();
        $retrievedToken = $this->service->getToken();
        
        $this->assertEquals($originalToken, $retrievedToken);
    }

    public function test_getToken_generates_new_token_if_none_exists()
    {
        $token = $this->service->getToken();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
        
        $tokenFile = $this->testCacheDir . '/csrf_token.txt';
        $this->assertFileExists($tokenFile);
        $this->assertEquals($token, file_get_contents($tokenFile));
    }

    public function test_validateToken_returns_true_for_valid_token()
    {
        $token = $this->service->generateToken();
        
        $result = $this->service->validateToken($token);
        
        $this->assertTrue($result);
    }

    public function test_validateToken_returns_false_for_invalid_token()
    {
        $this->service->generateToken();
        
        $result = $this->service->validateToken('invalid_token');
        
        $this->assertFalse($result);
    }

    public function test_validateToken_returns_false_when_no_token_in_session()
    {
        $result = $this->service->validateToken('any_token');
        
        $this->assertFalse($result);
    }

    public function test_validateTokenFromRequest_returns_true_for_valid_post_token()
    {
        $token = $this->service->generateToken();
        $request = Request::create('/test', 'POST', ['_token' => $token]);
        
        $result = $this->service->validateTokenFromRequest($request);
        
        $this->assertTrue($result);
    }

    public function test_validateTokenFromRequest_returns_true_for_valid_header_token()
    {
        $token = $this->service->generateToken();
        $request = Request::create('/test', 'POST');
        $request->headers->set('X-CSRF-Token', $token);
        
        $result = $this->service->validateTokenFromRequest($request);
        
        $this->assertTrue($result);
    }

    public function test_validateTokenFromRequest_returns_false_for_invalid_token()
    {
        $this->service->generateToken();
        $request = Request::create('/test', 'POST', ['_token' => 'invalid_token']);
        
        $result = $this->service->validateTokenFromRequest($request);
        
        $this->assertFalse($result);
    }

    public function test_validateTokenFromRequest_returns_false_when_no_token()
    {
        $this->service->generateToken();
        $request = Request::create('/test', 'POST');
        
        $result = $this->service->validateTokenFromRequest($request);
        
        $this->assertFalse($result);
    }
}
