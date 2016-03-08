<?php

class TestModelWithSearchableProperties extends TestModel
{
    protected $searchable = ['field1', 'field2'];

    protected $notSearchable = ['page'];
}