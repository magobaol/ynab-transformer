<?php

namespace Transformer;

use Transformer\Fineco;
use Transformer\Isybank;
use Transformer\Nexi;
use Transformer\Popso;
use Transformer\Poste;
use Transformer\Revolut;
use Transformer\Telepass;
use Transformer\Transformer;

class TransformerFactory
{
    /**
     * List of available transformers in order of popularity/usage
     */
    private const TRANSFORMERS = [
        'fineco' => Fineco::class,
        'revolut' => Revolut::class,
        'nexi' => Nexi::class,
        'popso' => Popso::class,
        'poste' => Poste::class,
        'telepass' => Telepass::class,
        'isybank' => Isybank::class,
    ];

    /**
     * Detect which transformer can handle the given file
     *
     * @param string $filename Path to the file to analyze
     * @return string The detected format name
     * @throws \Exception If no format is detected or multiple formats match
     */
    public static function detectFormat(string $filename): string
    {
        $detectedFormats = [];

        foreach (self::TRANSFORMERS as $format => $transformerClass) {
            if ($transformerClass::canHandle($filename)) {
                $detectedFormats[] = $format;
            }
        }

        if (empty($detectedFormats)) {
            $supportedFormats = implode(', ', array_keys(self::TRANSFORMERS));
            throw new \Exception("No supported format detected. Supported formats: {$supportedFormats}");
        }

        if (count($detectedFormats) > 1) {
            $detectedFormatsList = implode(', ', $detectedFormats);
            throw new \Exception("Multiple formats detected ({$detectedFormatsList}). Please specify manually with --format=<format>");
        }

        return $detectedFormats[0];
    }

    /**
     * Create a transformer instance for the specified format
     *
     * @param string $format The format name
     * @param string $filename Path to the file to transform
     * @return Transformer The transformer instance
     * @throws \Exception If the format is not supported
     */
    public static function create(string $format, string $filename): Transformer
    {
        if (!isset(self::TRANSFORMERS[$format])) {
            $supportedFormats = implode(', ', array_keys(self::TRANSFORMERS));
            throw new \Exception("Unsupported format '{$format}'. Supported formats: {$supportedFormats}");
        }

        $transformerClass = self::TRANSFORMERS[$format];
        return new $transformerClass($filename);
    }

    /**
     * Get list of all supported formats
     *
     * @return array List of supported format names
     */
    public static function getSupportedFormats(): array
    {
        return array_keys(self::TRANSFORMERS);
    }
}

