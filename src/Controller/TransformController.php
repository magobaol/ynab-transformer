<?php

namespace App\Controller;

use App\Service\FileProcessingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TransformController extends AbstractController
{
    private FileProcessingService $fileProcessingService;

    public function __construct(FileProcessingService $fileProcessingService)
    {
        $this->fileProcessingService = $fileProcessingService;
    }

    /**
     * Serve the main web interface
     */
    public function index(): Response
    {
        return $this->render('transform/index.html.twig', [
            'supportedFormats' => $this->fileProcessingService->getSupportedFormats()
        ]);
    }

    /**
     * Health check endpoint for monitoring
     */
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'ynab-transformer'
        ]);
    }

    /**
     * Process uploaded file and return CSV download
     */
    public function transform(Request $request): BinaryFileResponse
    {
        $uploadedFile = $request->files->get('file');
        
        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file uploaded');
        }

        try {
            // Validate the uploaded file
            if (!$this->fileProcessingService->validateUploadedFile($uploadedFile)) {
                throw new UnprocessableEntityHttpException('Invalid file type or size');
            }

            // Process the file and return CSV download
            return $this->fileProcessingService->processUploadedFile($uploadedFile);
            
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}