<?php

namespace Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\TransformController;
use App\Service\FileProcessingService;
use App\Service\RateLimitingService;
use App\Service\CsrfTokenService;

class TransformControllerTest extends TestCase
{
    private TransformController $controller;

    protected function setUp(): void
    {
        $this->controller = new TransformController(
            $this->createMock(FileProcessingService::class),
            $this->createMock(RateLimitingService::class),
            $this->createMock(CsrfTokenService::class),
        );
    }

    public function test_health_returns_json_response()
    {
        $response = $this->controller->health();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('ynab-transformer', $data['service']);
        $this->assertArrayHasKey('timestamp', $data);
    }
}
