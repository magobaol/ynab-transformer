<?php

namespace App\Controller;

use App\Service\FileProcessingService;
use App\Service\RateLimitingService;
use App\Service\CsrfTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TransformController extends AbstractController
{
    private FileProcessingService $fileProcessingService;
    private RateLimitingService $rateLimitingService;
    private CsrfTokenService $csrfTokenService;

    public function __construct(FileProcessingService $fileProcessingService, RateLimitingService $rateLimitingService, CsrfTokenService $csrfTokenService)
    {
        $this->fileProcessingService = $fileProcessingService;
        $this->rateLimitingService = $rateLimitingService;
        $this->csrfTokenService = $csrfTokenService;
    }

    /**
     * Serve the main web interface
     */
    public function index(): Response
    {
        return $this->render('transform/index.html.twig', [
            'supportedFormats' => $this->fileProcessingService->getSupportedFormats(),
            'csrf_token' => $this->csrfTokenService->getToken()
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

        // Validate CSRF token
        if (!$this->csrfTokenService->validateTokenFromRequest($request)) {
            throw new BadRequestHttpException('Invalid CSRF token');
        }

        // Get client IP and check rate limiting
        $clientIp = $this->rateLimitingService->getClientIp($request);
        
        // Skip rate limiting for whitelisted IPs (for testing)
        if (!$this->rateLimitingService->isWhitelisted($clientIp)) {
            if (!$this->rateLimitingService->isAllowed($clientIp)) {
                $timeUntilReset = $this->rateLimitingService->getTimeUntilReset($clientIp);
                throw new TooManyRequestsHttpException($timeUntilReset, 'Rate limit exceeded. Please try again later.');
            }
        }

        try {
            // Validate the uploaded file
            if (!$this->fileProcessingService->validateUploadedFile($uploadedFile)) {
                throw new UnprocessableEntityHttpException('Invalid file type or size');
            }

            // Record the request for rate limiting
            $this->rateLimitingService->recordRequest($clientIp);

            // Process the file and return CSV download
            return $this->fileProcessingService->processUploadedFile($uploadedFile);
            
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}