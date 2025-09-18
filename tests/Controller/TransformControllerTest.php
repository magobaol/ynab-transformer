<?php

namespace Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\TransformController;

class TransformControllerTest extends TestCase
{
    private TransformController $controller;

    protected function setUp(): void
    {
        $this->controller = new TransformController();
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
