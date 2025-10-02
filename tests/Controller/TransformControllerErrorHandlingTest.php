<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TransformControllerErrorHandlingTest extends WebTestCase
{
    public function testNoFileUploadedReturnsJsonError(): void
    {
        $client = static::createClient();
        
        // Make a POST request without any file
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
        
        // Create a temporary file with invalid extension
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');
        
        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );
        
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
        
        // Clean up
        unlink($tempFile);
    }

    public function testExceptionListenerHandlesApiRequests(): void
    {
        $client = static::createClient();
        
        // Make a request that would trigger an exception
        $client->request('POST', '/transform', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]);
        
        $response = $client->getResponse();
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }
}
