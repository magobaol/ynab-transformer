<?php

namespace App\Tests\Controller;

use App\Service\CsrfTokenService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TransformControllerIntegrationTest extends WebTestCase
{
    public function testNoFileUploadedReturnsJsonError(): void
    {
        $client = static::createClient();

        $client->request('POST', '/transform', [], [], [
            'HTTP_ACCEPT' => 'application/json'
        ]);

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Please select a file to upload.', $data['error']);
    }

    public function testInvalidFileTypeReturnsJsonError(): void
    {
        $client = static::createClient();
        $token = $this->mintCsrfToken();

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $client->request('POST', '/transform', ['_token' => $token], [
            'file' => $uploadedFile
        ], [
            'HTTP_ACCEPT' => 'application/json'
        ]);

        $response = $client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        unlink($tempFile);
    }

    public function testMissingCsrfTokenReturnsJsonError(): void
    {
        $client = static::createClient();

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $uploadedFile = new UploadedFile($tempFile, 'test.txt', 'text/plain', null, true);

        $client->request('POST', '/transform', [], [
            'file' => $uploadedFile
        ], [
            'HTTP_ACCEPT' => 'application/json'
        ]);

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        unlink($tempFile);
    }

    public function testExceptionListenerHandlesApiRequests(): void
    {
        $client = static::createClient();

        $client->request('POST', '/transform', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]);

        $response = $client->getResponse();
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testValidUploadReturnsCsvDownload(): void
    {
        $client = static::createClient();
        $token = $this->mintCsrfToken();

        // UploadedFile::move() physically moves the file, so work on a copy
        // to avoid destroying the fixture.
        $fixturePath = __DIR__ . '/../Fixtures/movimenti-isybank.xlsx';
        $tempCopy = sys_get_temp_dir() . '/upload_' . uniqid() . '.xlsx';
        copy($fixturePath, $tempCopy);

        $uploadedFile = new UploadedFile(
            $tempCopy,
            'statement.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $client->request('POST', '/transform', ['_token' => $token], [
            'file' => $uploadedFile
        ], [
            'HTTP_ACCEPT' => 'application/json'
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('-to-ynab.csv', $response->headers->get('Content-Disposition'));
    }

    private function mintCsrfToken(): string
    {
        return self::getContainer()->get(CsrfTokenService::class)->generateToken();
    }
}
