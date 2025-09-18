<?php

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\TransformationService;
use Model\Transaction\YNABTransactions;
use Model\Transaction\YNABTransaction;
use Transformer\TransformerFactory;

class TransformationServiceTest extends TestCase
{
    private TransformationService $service;
    private TransformerFactory $transformerFactory;

    protected function setUp(): void
    {
        $this->transformerFactory = new TransformerFactory();
        $this->service = new TransformationService($this->transformerFactory);
    }

    public function test_getSupportedFormats_returns_formats_from_factory()
    {
        $result = $this->service->getSupportedFormats();

        $this->assertIsArray($result);
        $this->assertContains('fineco', $result);
        $this->assertContains('revolut', $result);
        $this->assertContains('nexi', $result);
        $this->assertContains('popso', $result);
        $this->assertContains('poste', $result);
        $this->assertContains('telepass', $result);
        $this->assertContains('isybank', $result);
    }
}
