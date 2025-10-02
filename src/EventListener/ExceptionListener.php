<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle exceptions for AJAX requests or API endpoints
        if (!$this->isApiRequest($request)) {
            return;
        }

        $this->logger->error('Exception occurred', [
            'exception' => $exception,
            'request_uri' => $request->getUri(),
            'request_method' => $request->getMethod(),
        ]);

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An error occurred while processing your request.';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        }

        $response = new JsonResponse([
            'error' => $message,
            'status' => $statusCode
        ], $statusCode);

        $event->setResponse($response);
    }

    private function isApiRequest($request): bool
    {
        // Check if it's an AJAX request
        if ($request->isXmlHttpRequest()) {
            return true;
        }

        // Check if it's a POST request to the transform endpoint
        if ($request->getMethod() === 'POST' && $request->getPathInfo() === '/transform') {
            return true;
        }

        // Check if the request expects JSON response
        $acceptHeader = $request->headers->get('Accept', '');
        if (strpos($acceptHeader, 'application/json') !== false) {
            return true;
        }

        return false;
    }
}
