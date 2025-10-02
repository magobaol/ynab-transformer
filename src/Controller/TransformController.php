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
    public function transform(Request $request): Response
    {
        $uploadedFile = $request->files->get('file');
        
        if (!$uploadedFile) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        // Validate CSRF token
        if (!$this->csrfTokenService->validateTokenFromRequest($request)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        // Get client IP and check rate limiting
        $clientIp = $this->rateLimitingService->getClientIp($request);
        
        // Skip rate limiting for whitelisted IPs (for testing)
        if (!$this->rateLimitingService->isWhitelisted($clientIp)) {
            if (!$this->rateLimitingService->isAllowed($clientIp)) {
                $timeUntilReset = $this->rateLimitingService->getTimeUntilReset($clientIp);
                return new JsonResponse(['error' => 'Rate limit exceeded. Please try again later.'], 429);
            }
        }

        try {
            // Validate the uploaded file
            if (!$this->fileProcessingService->validateUploadedFile($uploadedFile)) {
                return new JsonResponse(['error' => 'Invalid file type or size'], 422);
            }

            // Record the request for rate limiting
            $this->rateLimitingService->recordRequest($clientIp);


            // Process the file and return CSV download
            return $this->fileProcessingService->processUploadedFile($uploadedFile);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        }
    }
}