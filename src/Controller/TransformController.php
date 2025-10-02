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
            return new JsonResponse(['error' => 'Please select a file to upload.'], 400);
        }

        // Validate CSRF token
        if (!$this->csrfTokenService->validateTokenFromRequest($request)) {
            return new JsonResponse(['error' => 'Security validation failed. Please refresh the page and try again.'], 400);
        }

        // Get client IP and check rate limiting
        $clientIp = $this->rateLimitingService->getClientIp($request);
        
        // Skip rate limiting for whitelisted IPs (for testing)
        if (!$this->rateLimitingService->isWhitelisted($clientIp)) {
            if (!$this->rateLimitingService->isAllowed($clientIp)) {
                $timeUntilReset = $this->rateLimitingService->getTimeUntilReset($clientIp);
                return new JsonResponse(['error' => 'Too many upload attempts. Please wait a few minutes before trying again.'], 429);
            }
        }

        try {
            // Validate the uploaded file
            if (!$this->fileProcessingService->validateUploadedFile($uploadedFile)) {
                return new JsonResponse(['error' => 'Please upload a valid Excel (.xlsx, .xls) or CSV file. Maximum file size is 1MB.'], 422);
            }

            // Record the request for rate limiting
            $this->rateLimitingService->recordRequest($clientIp);

            // Process the file and return CSV download
            return $this->fileProcessingService->processUploadedFile($uploadedFile);
            
        } catch (\Exception $e) {
            $userFriendlyMessage = $this->getUserFriendlyErrorMessage($e);
            return new JsonResponse(['error' => $userFriendlyMessage], 422);
        }
    }

    /**
     * Convert technical exception messages to user-friendly error messages
     */
    private function getUserFriendlyErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();
        
        // Handle unrecognized bank format
        if (strpos($message, 'No supported format detected') !== false) {
            $supportedBanks = $this->fileProcessingService->getSupportedFormats();
            $bankList = implode(', ', array_map('ucfirst', $supportedBanks));
            return "We couldn't recognize your bank statement format. Currently supported banks: {$bankList}. Please make sure your file is from one of these banks or contact us if you need support for a new bank.";
        }
        
        // Handle multiple formats detected
        if (strpos($message, 'Multiple formats detected') !== false) {
            return "Your file could match multiple bank formats. Please try uploading a file from a single bank account to avoid confusion.";
        }
        
        // Handle unsupported format
        if (strpos($message, 'Unsupported format') !== false) {
            $supportedBanks = $this->fileProcessingService->getSupportedFormats();
            $bankList = implode(', ', array_map('ucfirst', $supportedBanks));
            return "This bank format is not supported yet. Currently supported banks: {$bankList}. Please contact us if you need support for a new bank.";
        }
        
        // Handle file processing errors
        if (strpos($message, 'Failed to generate CSV file') !== false) {
            return "There was an error processing your file. Please make sure the file is not corrupted and try again.";
        }
        
        if (strpos($message, 'Failed to store uploaded file') !== false) {
            return "There was an error saving your file. Please try again.";
        }
        
        // Handle format detection errors
        if (strpos($message, 'Format must be specified') !== false) {
            return "There was an error with the file format detection. Please try again.";
        }
        
        // Default fallback for unknown errors
        return "There was an unexpected error processing your file. Please make sure your file is from a supported bank and try again.";
    }
}