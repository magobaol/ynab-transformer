<?php

namespace Transformer;

use Model\Transaction\YNABTransactions;

interface Transformer
{
    public function transformToYNAB(): YNABTransactions;
}