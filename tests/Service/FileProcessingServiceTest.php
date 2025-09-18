<?php

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Service\FileProcessingService;
use App\Service\TransformationService;
use Model\Transaction\YNABTransactions;

class FileProcessingServiceTest extends TestCase
{
    private FileProcessingService $service;
    private TransformationService $transformationService;

    protected function setUp(): void
    {
        $this->transformationService = $this->createMock(TransformationService::class);
        $this->service = new FileProcessingService($this->transformationService, '/tmp');
    }

    public function test_validateUploadedFile_with_valid_xlsx_file_returns_true()
    {
        $file = $this->createMockUploadedFile('movimenti-fineco.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1024);

        $result = $this->service->validateUploadedFile($file);

        $this->assertTrue($result);
    }

    public function test_validateUploadedFile_with_valid_csv_file_returns_true()
    {
        $file = $this->createMockUploadedFile('movimenti-revolut.csv', 'text/csv', 512);

        $result = $this->service->validateUploadedFile($file);

        $this->assertTrue($result);
    }

    public function test_validateUploadedFile_with_invalid_file_type_returns_false()
    {
        $file = $this->createMockUploadedFile('document.pdf', 'application/pdf', 1024);

        $result = $this->service->validateUploadedFile($file);

        $this->assertFalse($result);
    }

    public function test_validateUploadedFile_with_file_too_large_returns_false()
    {
        $file = $this->createMockUploadedFile('large.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1048577); // Just over 1MB

        $result = $this->service->validateUploadedFile($file);

        $this->assertFalse($result);
    }

    public function test_validateUploadedFile_with_invalid_extension_returns_false()
    {
        $file = $this->createMockUploadedFile('document.txt', 'text/plain', 1024);

        $result = $this->service->validateUploadedFile($file);

        $this->assertFalse($result);
    }

    private function createMockUploadedFile(string $originalName, string $mimeType, int $size, int $error = UPLOAD_ERR_OK): UploadedFile
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn($originalName);
        $file->method('getClientOriginalExtension')->willReturn(pathinfo($originalName, PATHINFO_EXTENSION));
        $file->method('getMimeType')->willReturn($mimeType);
        $file->method('getSize')->willReturn($size);
        $file->method('getError')->willReturn($error);
        $file->method('isValid')->willReturn($error === UPLOAD_ERR_OK);
        $file->method('getPathname')->willReturn('/tmp/' . $originalName);
        
        return $file;
    }
}
