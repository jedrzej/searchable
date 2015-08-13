<?php

class TestModelWithAllFieldsSearchable extends TestModel
{
    protected $searchable = ['field1', '*'];
}