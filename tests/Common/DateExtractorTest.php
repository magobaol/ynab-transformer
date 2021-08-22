<?php

namespace Tests\Common;

use Carbon\Carbon;
use Common\DateExtractor;
use PHPUnit\Framework\TestCase;

class DateExtractorTest extends TestCase
{
    public function test_extractFromString_with_2digitYear()
    {
        $date = DateExtractor::extractFromString('this is a string with a date 02/09/21 inside');

        $this->assertEquals(Carbon::create(2021, 9, 2), $date);
    }

    public function test_extractFromString_with_4digitYear()
    {
        $date = DateExtractor::extractFromString('this is a string with a date 03/10/2021 inside');

        $this->assertEquals(Carbon::create(2021, 10, 3), $date);
    }

    public function test_extractFromString_with_1digitMonth()
    {
        $date = DateExtractor::extractFromString('this is a string with a date 02/9/21 inside');

        $this->assertEquals(Carbon::create(2021, 9, 2), $date);
    }

    public function test_extractFromString_with_2digitMonth()
    {
        $date = DateExtractor::extractFromString('this is a string with a date 02/09/21 inside');

        $this->assertEquals(Carbon::create(2021, 9, 2), $date);
    }

    public function test_extractFromString_with_1digitDay()
    {
        $date = DateExtractor::extractFromString('this is a string with a date 2/09/21 inside');

        $this->assertEquals(Carbon::create(2021, 9, 2), $date);
    }

    public function test_extractFromString_with_2digitDay()
    {
        $date = DateExtractor::extractFromString('this is a string with a date 02/09/21 inside');

        $this->assertEquals(Carbon::create(2021, 9, 2), $date);
    }

    public function test_extractFromString_without_a_date_returns_null()
    {
        $date = DateExtractor::extractFromString('this is a string without a date');

        $this->assertEquals(null, $date);
    }

}