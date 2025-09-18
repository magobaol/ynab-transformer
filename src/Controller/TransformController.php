<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransformController extends AbstractController
{
    /**
     * Serve the main web interface
     */
    public function index(): Response
    {
        return new Response('<h1>YNAB Transformer</h1><p>Web interface coming soon!</p>');
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
}