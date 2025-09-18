<?php

namespace App\Service;

use Common\FileNameGenerator;
use Model\Transaction\YNABTransactions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileProcessingService
{
    private string $tempDir;
    private TransformationService $transformationService;

    public function __construct(TransformationService $transformationService, string $tempDir = '/tmp')
    {
        $this->transformationService = $transformationService;
        $this->tempDir = rtrim($tempDir, '/');
    }

    /**
     * Process an uploaded file and return a CSV download response
     *
     * @param UploadedFile $uploadedFile The uploaded file
     * @param bool $autoDetect Whether to auto-detect the format
     * @param string|null $format Specific format to use (if not auto-detecting)
     * @return BinaryFileResponse The CSV file download response
     * @throws \Exception If processing fails
     */
    public function processUploadedFile(UploadedFile $uploadedFile, bool $autoDetect = true, ?string $format = null): BinaryFileResponse
    {
        $tempFilePath = $this->storeTemporaryFile($uploadedFile);
        
        try {
            if ($autoDetect) {
                $result = $this->transformationService->transformWithAutoDetection($tempFilePath);
                $transactions = $result['transactions'];
                $detectedFormat = $result['format'];
            } else {
                if (!$format) {
                    throw new \Exception('Format must be specified when auto-detection is disabled');
                }
                $transactions = $this->transformationService->transformWithFormat($tempFilePath, $format);
                $detectedFormat = $format;
            }

            $csvFilePath = $this->generateCsvFile($tempFilePath, $transactions);
            
            return $this->createDownloadResponse($csvFilePath, $uploadedFile->getClientOriginalName(), $detectedFormat);
            
        } finally {
            // Always clean up the temporary uploaded file
            $this->cleanupFile($tempFilePath);
        }
    }

    /**
     * Store an uploaded file temporarily
     *
     * @param UploadedFile $uploadedFile The uploaded file
     * @return string Path to the temporary file
     * @throws \Exception If file storage fails
     */
    private function storeTemporaryFile(UploadedFile $uploadedFile): string
    {
        $tempFileName = uniqid('ynab_upload_', true) . '.' . $uploadedFile->getClientOriginalExtension();
        $tempFilePath = $this->tempDir . '/' . $tempFileName;

        if (!$uploadedFile->move($this->tempDir, $tempFileName)) {
            throw new \Exception('Failed to store uploaded file temporarily');
        }

        return $tempFilePath;
    }

    /**
     * Generate a CSV file from transactions
     *
     * @param string $originalFilePath Path to the original file (for naming)
     * @param YNABTransactions $transactions The transactions to convert
     * @return string Path to the generated CSV file
     * @throws \Exception If CSV generation fails
     */
    private function generateCsvFile(string $originalFilePath, YNABTransactions $transactions): string
    {
        $fileNameGenerator = FileNameGenerator::fromSourceFilename($originalFilePath)
            ->withSuffix('-to-ynab')
            ->withExtension('csv')
            ->avoidDuplicates();
        $csvFilePath = $fileNameGenerator->generate();
        
        try {
            $transactions->toCSVFile($csvFilePath);
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate CSV file: ' . $e->getMessage());
        }

        return $csvFilePath;
    }

    /**
     * Create a file download response
     *
     * @param string $filePath Path to the file to download
     * @param string $originalFileName Original filename for download
     * @param string $format The detected/specified format
     * @return BinaryFileResponse The download response
     */
    private function createDownloadResponse(string $filePath, string $originalFileName, string $format): BinaryFileResponse
    {
        $response = new BinaryFileResponse($filePath);
        
        // Use the filename from the generated CSV file
        $downloadFileName = basename($filePath);
        
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $downloadFileName
        );
        
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        // Set up cleanup after response is sent
        $response->deleteFileAfterSend(true);
        
        return $response;
    }

    /**
     * Clean up a temporary file
     *
     * @param string $filePath Path to the file to delete
     */
    private function cleanupFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Validate an uploaded file
     *
     * @param UploadedFile $uploadedFile The uploaded file
     * @param int $maxSize Maximum file size in bytes
     * @return bool True if valid, false otherwise
     */
    public function validateUploadedFile(UploadedFile $uploadedFile, int $maxSize = 1048576): bool
    {
        // Check file size
        if ($uploadedFile->getSize() > $maxSize) {
            return false;
        }

        // Check file type
        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        // Check MIME type
        $allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel', // .xls
            'text/csv', // .csv
            'application/csv', // .csv
        ];
        
        if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            return false;
        }

        return true;
    }
}

