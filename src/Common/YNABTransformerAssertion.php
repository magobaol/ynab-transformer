<?php

namespace Common;

use Assert\Assertion as BaseAssertion;

class YNABTransformerAssertion extends BaseAssertion
{
    protected static $exceptionClass = 'Common\Exception\AssertionFailed';
}