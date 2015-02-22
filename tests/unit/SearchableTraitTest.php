<?php

use Codeception\Specify;
use Codeception\TestCase\Test;

class SearchableTraitTest extends Test
{
    use Specify;

    public function testConstraints()
    {
        $this->specify("constraints are applied when query is given", function () {
            $this->assertCount(1, (array)TestModel::filtered(['field1' => 5])->getQuery()->wheres);
            $this->assertCount(2, (array)TestModel::filtered(['field1' => 5, 'field2' => 3])->getQuery()->wheres);
        });

        $this->specify("constraints are applied only to searchable parameters", function () {
            $this->assertCount(0, (array)TestModel::filtered(['field3' => 5])->getQuery()->wheres);
            $this->assertCount(1, (array)TestModel::filtered(['field3' => 5, 'field2' => 3])->getQuery()->wheres);
        });

        $this->specify("constraints are applied to columns by name", function() {
            $where = TestModel::filtered(['field1' => '!abc,cde'])->getQuery()->wheres[0];
            $this->assertEquals('field1', $where['column']);
        });

        $this->specify("constraints are applied correctly", function() {

            $where = TestModel::filtered(['field1' => 'abc'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc', $where['value']);
            $this->assertEquals('=', $where['operator']);

            $where = TestModel::filtered(['field1' => 'abc,cde'])->getQuery()->wheres[0];
            $this->assertEquals('In', $where['type']);
            $this->assertEquals(['abc','cde'], $where['values']);

            $where = TestModel::filtered(['field1' => '(gt)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('>', $where['operator']);

            $where = TestModel::filtered(['field1' => '(ge)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('>=', $where['operator']);

            $where = TestModel::filtered(['field1' => '(lt)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('<', $where['operator']);

            $where = TestModel::filtered(['field1' => '(le)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('<=', $where['operator']);

            $where = TestModel::filtered(['field1' => 'abc%'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc%', $where['value']);
            $this->assertEquals('like', $where['operator']);

            $where = TestModel::filtered(['field1' => '!abc'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc', $where['value']);
            $this->assertEquals('<>', $where['operator']);

            $where = TestModel::filtered(['field1' => '!abc,cde'])->getQuery()->wheres[0];
            $this->assertEquals('NotIn', $where['type']);
            $this->assertEquals(['abc','cde'], $where['values']);

            $where = TestModel::filtered(['field1' => '!(gt)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('<=', $where['operator']);

            $where = TestModel::filtered(['field1' => '!(ge)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('<', $where['operator']);

            $where = TestModel::filtered(['field1' => '!(lt)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('>=', $where['operator']);

            $where = TestModel::filtered(['field1' => '!(le)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('>', $where['operator']);

            $where = TestModel::filtered(['field1' => '!abc%'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc%', $where['value']);
            $this->assertEquals('not like', $where['operator']);
        });

        $this->specify("models are able to handle selected query constraints themselves", function() {
            $where = TestModel::filtered(['field2' => 'abc,cde'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc', $where['value']);
            $this->assertEquals('=', $where['operator']);
        });
    }
}
