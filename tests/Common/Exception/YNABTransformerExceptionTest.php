<?php

namespace Tests\Common\Exception;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class YNABTransformerExceptionTest extends TestCase
{
    public function test_ErrorCodesAreUnique()
    {
        $exceptionClass = new ReflectionClass('Common\Exception\YNABTransformerException');
        $errorsDefined = $exceptionClass->getConstants();
        $uniqueErrorCodes = array_unique($errorsDefined);
        $notUniqueErrorCodes = implode(', ', array_diff_assoc($errorsDefined, $uniqueErrorCodes));

        $this->assertEquals(count($errorsDefined), count($uniqueErrorCodes), 'Not unique codes are: '.$notUniqueErrorCodes);
    }
}