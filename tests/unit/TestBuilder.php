<?php

use Illuminate\Database\Query\Builder;

class TestBuilder extends Builder
{
    public function __construct(ConnectionInterface $connection = null, Grammar $grammar = null, Processor $processor = null)
    {
        //
    }
}