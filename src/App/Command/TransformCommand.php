<?php

namespace App\Command;

use Common\FileNameGenerator;
use Symfony\Component\Console\Input\InputOption;
use Transformer\Fineco;
use Transformer\Nexi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Transformer\Popso;

#[AsCommand(
    name: 'app:transform',
    description: 'Add a short description for your command',
)]
class TransformCommand extends Command
{
    private static array $formats = [
        'nexi',
        'popso',
        'fineco'
    ];

    protected function configure(): void
    {
        $this
            ->addArgument('input', InputArgument::REQUIRED, 'The input file in Nexi format')
            ->addOption('format', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('format')) {
            $output->writeln('You have to specify the format with --format');
            return Command::INVALID;
        }

        if (!in_array($input->getOption('format'), self::$formats)) {
            $output->writeln('Accepted formats are '.implode(', ', self::$formats));
            return Command::INVALID;
        }

        $sourceFileName = $input->getArgument('input');
        $transformer = match ($input->getOption('format')) {
            'nexi' => new Nexi($sourceFileName),
            'popso' => new Popso($sourceFileName),
            'fineco' => new Fineco($sourceFileName)
        };

        $ynabTransactions = $transformer->transformToYNAB();

        $targetFileName =
            FileNameGenerator::fromSourceFilename($sourceFileName)
                ->withSuffix('-to-ynab')
                ->withExtension('csv')
                ->avoidDuplicates()
                ->generate();

        $ynabTransactions->toCSVFile($targetFileName);
        $output->writeln('Transformation completed in '.$targetFileName);
        return Command::SUCCESS;
    }
}