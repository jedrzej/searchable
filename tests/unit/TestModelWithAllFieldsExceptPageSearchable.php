<?php

class TestModelWithAllFieldsExceptPageSearchable extends TestModel
{
    protected $searchable = ['field1', '*'];

    protected $notSearchable = ['page'];
}