<?php

namespace App\Service;

use Model\Transaction\YNABTransactions;
use Transformer\Transformer;

class TransformationService
{
    private TransformerFactory $transformerFactory;

    public function __construct(TransformerFactory $transformerFactory)
    {
        $this->transformerFactory = $transformerFactory;
    }

    /**
     * Transform a file to YNAB format with automatic format detection
     *
     * @param string $filename Path to the input file
     * @return array Array containing 'transactions' (YNABTransactions) and 'format' (detected format)
     * @throws \Exception If format detection or transformation fails
     */
    public function transformWithAutoDetection(string $filename): array
    {
        $detectedFormat = $this->transformerFactory->detectFormat($filename);
        $transformer = $this->transformerFactory->create($detectedFormat, $filename);
        $transactions = $transformer->transformToYNAB();

        return [
            'transactions' => $transactions,
            'format' => $detectedFormat
        ];
    }

    /**
     * Transform a file to YNAB format with specified format
     *
     * @param string $filename Path to the input file
     * @param string $format The format to use for transformation
     * @return YNABTransactions The transformed transactions
     * @throws \Exception If transformation fails
     */
    public function transformWithFormat(string $filename, string $format): YNABTransactions
    {
        $transformer = $this->transformerFactory->create($format, $filename);
        return $transformer->transformToYNAB();
    }

    /**
     * Get the list of supported formats
     *
     * @return array List of supported format names
     */
    public function getSupportedFormats(): array
    {
        return $this->transformerFactory->getSupportedFormats();
    }
}

