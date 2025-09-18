<?php

namespace App\Command;

use Common\FileNameGenerator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Transformer\TransformerFactory;

#[AsCommand(
    name: 'app:transform',
    description: 'Add a short description for your command',
)]
class TransformCommand extends Command
{

    protected function configure(): void
    {
        $this
            ->addArgument('input', InputArgument::REQUIRED, 'The input file to transform')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The bank format (optional - will auto-detect if not specified)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceFileName = $input->getArgument('input');
        $format = $input->getOption('format');

        // If format is specified, validate it and use it
        if ($format) {
            $supportedFormats = TransformerFactory::getSupportedFormats();
            if (!in_array($format, $supportedFormats)) {
                $output->writeln('Accepted formats are '.implode(', ', $supportedFormats));
                return Command::INVALID;
            }
            
            // When format is specified, validate that the detected format matches
            try {
                $detectedFormat = TransformerFactory::detectFormat($sourceFileName);
                if ($detectedFormat !== $format) {
                    $output->writeln("Warning: File appears to be '{$detectedFormat}' format, but '{$format}' was specified. Using specified format.");
                }
                $transformer = $this->createTransformerByFormat($format, $sourceFileName);
            } catch (\Exception $e) {
                // If auto-detection fails but format is specified, still try to use the specified format
                $transformer = $this->createTransformerByFormat($format, $sourceFileName);
            }
        } else {
            // Auto-detect format
            try {
                $detectedFormat = TransformerFactory::detectFormat($sourceFileName);
                $output->writeln("Format '{$detectedFormat}' detected, processing...");
                $transformer = TransformerFactory::create($detectedFormat, $sourceFileName);
            } catch (\Exception $e) {
                $supportedFormats = implode(', ', TransformerFactory::getSupportedFormats());
                $output->writeln("No supported format detected. Supported formats: {$supportedFormats}");
                return Command::FAILURE;
            }
        }

        $ynabTransactions = $transformer->transformToYNAB();

        $targetFileName =
            FileNameGenerator::fromSourceFilename($sourceFileName)
                ->withSuffix('-to-ynab')
                ->withExtension('csv')
                ->avoidDuplicates()
                ->generate();

        $ynabTransactions->toCSVFile($targetFileName);
        $output->writeln($targetFileName);
        return Command::SUCCESS;
    }

    private function createTransformerByFormat(string $format, string $sourceFileName): object
    {
        // Import the transformer classes
        $transformerClasses = [
            'fineco' => \Transformer\Fineco::class,
            'revolut' => \Transformer\Revolut::class,
            'nexi' => \Transformer\Nexi::class,
            'popso' => \Transformer\Popso::class,
            'poste' => \Transformer\Poste::class,
            'telepass' => \Transformer\Telepass::class,
            'isybank' => \Transformer\Isybank::class,
        ];

        if (!isset($transformerClasses[$format])) {
            throw new \InvalidArgumentException("Unknown format: {$format}");
        }

        $transformerClass = $transformerClasses[$format];
        return new $transformerClass($sourceFileName);
    }
}