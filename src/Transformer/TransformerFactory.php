<?php

namespace Transformer;

use Exception;

class TransformerFactory
{
    private static array $transformers = [
        'fineco' => Fineco::class,
        'revolut' => Revolut::class,
        'nexi' => Nexi::class,
        'popso' => Popso::class,
        'poste' => Poste::class,
        'telepass' => Telepass::class,
        'isybank' => Isybank::class,
    ];

    /**
     * Detect the format of the given file by checking each transformer's canHandle method
     * 
     * @param string $filename
     * @return string The detected format name
     * @throws Exception if no format is detected or multiple formats match
     */
    public static function detectFormat(string $filename): string
    {
        $detectedFormats = [];
        
        foreach (self::$transformers as $formatName => $transformerClass) {
            if ($transformerClass::canHandle($filename)) {
                $detectedFormats[] = $formatName;
            }
        }
        
        if (empty($detectedFormats)) {
            throw new Exception('No supported format detected for file: ' . basename($filename));
        }
        
        if (count($detectedFormats) > 1) {
            // Log all matched formats for debugging
            error_log('Multiple formats detected for file ' . basename($filename) . ': ' . implode(', ', $detectedFormats));
            throw new Exception('Multiple formats detected for file: ' . basename($filename));
        }
        
        return $detectedFormats[0];
    }

    /**
     * Create a transformer instance for the given file
     * 
     * @param string $filename
     * @return Transformer
     * @throws Exception if no format is detected
     */
    public static function create(string $filename): Transformer
    {
        $format = self::detectFormat($filename);
        $transformerClass = self::$transformers[$format];
        
        return new $transformerClass($filename);
    }

    /**
     * Get a list of all supported format names
     * 
     * @return array
     */
    public static function getSupportedFormats(): array
    {
        return array_keys(self::$transformers);
    }
}