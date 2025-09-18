<?php

namespace Tests\App\Command;

use App\Command\TransformCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TransformCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private Application $application;

    protected function setUp(): void
    {
        $this->application = new Application();
        $this->application->add(new TransformCommand());
        
        $command = $this->application->find('app:transform');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        // Clean up any generated CSV files in fixtures directory
        $fixturesDir = __DIR__ . '/../../Fixtures/';
        $pattern = $fixturesDir . '*-to-ynab*.csv';
        $files = glob($pattern);
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }


    public function test_command_with_format_parameter_works_as_before()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-fineco.xlsx',
            '--format' => 'fineco'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_without_format_parameter_auto_detects_fineco()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-fineco.xlsx'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Format 'fineco' detected, processing...", $output);
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_without_format_parameter_auto_detects_revolut()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-revolut.csv'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Format 'revolut' detected, processing...", $output);
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_without_format_parameter_auto_detects_nexi()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-nexi.xlsx'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Format 'nexi' detected, processing...", $output);
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_without_format_parameter_auto_detects_popso()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-popso.csv'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Format 'popso' detected, processing...", $output);
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_without_format_parameter_auto_detects_poste()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-poste.xlsx'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Format 'poste' detected, processing...", $output);
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_without_format_parameter_auto_detects_telepass()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-telepass.xls'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Format 'telepass' detected, processing...", $output);
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_without_format_parameter_auto_detects_isybank()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-isybank.xlsx'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Format 'isybank' detected, processing...", $output);
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_without_format_parameter_handles_unknown_format()
    {
        $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/filename.xlsx'
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('No supported format detected', $output);
        $this->assertStringContainsString('Supported formats: fineco, revolut, nexi, popso, poste, telepass, isybank', $output);
    }

    public function test_command_without_format_parameter_handles_nonexistent_file()
    {
        $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/nonexistent.xlsx'
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('No supported format detected', $output);
    }

    public function test_command_with_invalid_format_parameter_shows_error()
    {
        $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-fineco.xlsx',
            '--format' => 'invalid'
        ]);

        $this->assertEquals(Command::INVALID, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Accepted formats are', $output);
    }

    public function test_command_with_format_parameter_shows_specified_format()
    {
        $exitCode = $this->commandTester->execute([
            'input' => __DIR__ . '/../../Fixtures/movimenti-fineco.xlsx',
            '--format' => 'fineco'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        // Should not contain auto-detection message when format is specified
        $this->assertStringNotContainsString("Format 'fineco' detected", $output);
        $this->assertStringContainsString('.csv', $output);
    }

    public function test_command_help_shows_optional_format_parameter()
    {
        $command = $this->application->find('app:transform');
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('format'));
        $formatOption = $definition->getOption('format');
        $this->assertStringContainsString('optional', $formatOption->getDescription());
    }
}
