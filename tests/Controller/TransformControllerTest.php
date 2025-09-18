<?php

namespace Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\TransformController;
use App\Service\FileProcessingService;

class TransformControllerTest extends TestCase
{
    private TransformController $controller;
    private FileProcessingService $fileProcessingService;

    protected function setUp(): void
    {
        $this->fileProcessingService = $this->createMock(FileProcessingService::class);
        $this->controller = new TransformController($this->fileProcessingService);
    }

    public function test_index_returns_response()
    {
        $response = $this->controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('YNAB Transformer', $response->getContent());
        $this->assertStringContainsString('Web interface coming soon', $response->getContent());
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
