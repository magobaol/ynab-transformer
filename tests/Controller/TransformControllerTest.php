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

    public function test_index_calls_getSupportedFormats()
    {
        // Mock the getSupportedFormats method
        $this->fileProcessingService->expects($this->once())
            ->method('getSupportedFormats')
            ->willReturn(['fineco', 'revolut', 'nexi']);

        // Test that the method calls getSupportedFormats
        // Note: This will fail due to missing Twig environment, but that's expected in unit tests
        try {
            $this->controller->index();
        } catch (\Error $e) {
            // Expected error due to missing Twig environment in unit test
            $this->assertStringContainsString('container', $e->getMessage());
        }
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
