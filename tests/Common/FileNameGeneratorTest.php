<?php

namespace Tests\Common;

use Carbon\Carbon;
use Common\FileNameGenerator;
use PHPUnit\Framework\TestCase;

class FileNameGeneratorTest extends TestCase
{
    public function setUp(): void
    {
        Carbon::setTestNow();
    }

    public function test_create_without_personalization_returns_original_filename()
    {
        $filename = '/home/francesco/dev/path/filename.xlsx';

        $newFileName =
            FileNameGenerator::fromSourceFilename($filename)
                ->generate();

        $this->assertEquals($newFileName, $filename);
    }

    public function test_create_with_suffix_returns_original_filename_with_suffix()
    {
        $filename = '/home/francesco/dev/path/filename.xlsx';

        $newFileName =
            FileNameGenerator::fromSourceFilename($filename)
                ->withSuffix('-to-ynab')
                ->generate();

        $this->assertEquals('/home/francesco/dev/path/filename-to-ynab.xlsx', $newFileName);
    }

    public function test_create_with_different_extension_returns_original_filename_with_new_extension()
    {
        $filename = '/home/francesco/dev/path/filename.xlsx';

        $newFileName =
            FileNameGenerator::fromSourceFilename($filename)
                ->withExtension('csv')
                ->generate();

        $this->assertEquals('/home/francesco/dev/path/filename.csv', $newFileName);
    }

    public function test_create_without_personalization_returns_original_filename_without_duplicate()
    {
        Carbon::setTestNow('2021-08-22 02:01:00');
        $filename = __DIR__.'/../Fixtures/filename.xlsx';

        $newFileName =
            FileNameGenerator::fromSourceFilename($filename)
                ->avoidDuplicates()
                ->generate();

        $this->assertEquals(__DIR__.'/../Fixtures/filename-2021-08-22_02-01-00.xlsx', $newFileName);
    }

    public function test_create_with_suffix_returns_original_filename_without_duplicate()
    {
        Carbon::setTestNow('2021-08-22 02:01:00');
        $filename = __DIR__.'/../Fixtures/filename.xlsx';

        $newFileName =
            FileNameGenerator::fromSourceFilename($filename)
                ->withSuffix('-to-ynab')
                ->avoidDuplicates()
                ->generate();

        $this->assertEquals(__DIR__.'/../Fixtures/filename-to-ynab-2021-08-22_02-01-00.xlsx', $newFileName);
    }

}