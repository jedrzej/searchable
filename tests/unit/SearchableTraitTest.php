<?php

use Codeception\Specify;
use Codeception\TestCase\Test;
use Jedrzej\Searchable\Constraint;

class SearchableTraitTest extends Test
{
    use Specify;

    public function testConstraints()
    {
        $this->specify("constraints are applied when query is given", function () {
            $this->assertCount(1, (array)TestModelWithSearchableMethod::filtered(['field1' => 5])->getQuery()->wheres);
            $this->assertCount(2, (array)TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3])->getQuery()->wheres);
        });

        $this->specify("constraints are applied only to searchable parameters", function () {
            $this->assertCount(0, (array)TestModelWithSearchableMethod::filtered(['field3' => 5])->getQuery()->wheres);
            $this->assertCount(1, (array)TestModelWithSearchableMethod::filtered(['field3' => 5, 'field2' => 3])->getQuery()->wheres);
        });

        $this->specify("constraints are applied to columns by name", function () {
            $where = TestModelWithSearchableMethod::filtered(['field1' => '!abc,cde'])->getQuery()->wheres[0];
            $this->assertEquals('field1', $where['column']);
        });

        $this->specify("constraints are applied correctly", function () {

            $where = TestModelWithSearchableMethod::filtered(['field1' => 'abc'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc', $where['value']);
            $this->assertEquals('=', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => 'abc,cde'])->getQuery()->wheres[0];
            $this->assertEquals('In', $where['type']);
            $this->assertEquals(['abc', 'cde'], $where['values']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '(gt)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('>', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '(ge)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('>=', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '(lt)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('<', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '(le)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('<=', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => 'abc%'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc%', $where['value']);
            $this->assertEquals('like', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '!abc'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc', $where['value']);
            $this->assertEquals('<>', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '!abc,cde'])->getQuery()->wheres[0];
            $this->assertEquals('NotIn', $where['type']);
            $this->assertEquals(['abc', 'cde'], $where['values']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '!(gt)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('<=', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '!(ge)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('<', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '!(lt)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('>=', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '!(le)5'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('5', $where['value']);
            $this->assertEquals('>', $where['operator']);
            $this->assertEquals('field1', $where['column']);

            $where = TestModelWithSearchableMethod::filtered(['field1' => '!abc%'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc%', $where['value']);
            $this->assertEquals('not like', $where['operator']);
            $this->assertEquals('field1', $where['column']);
        });

        $this->specify("models are able to handle selected query constraints themselves", function () {
            $where = TestModelWithSearchableMethod::filtered(['field2' => 'abc,cde'])->getQuery()->wheres[0];
            $this->assertEquals('Basic', $where['type']);
            $this->assertEquals('abc', $where['value']);
            $this->assertEquals('=', $where['operator']);
            $this->assertEquals('field2', $where['column']);
        });

        $this->specify("multiple constraints can be given for a single attribute", function () {
            $wheres = (array)TestModelWithSearchableMethod::filtered(['field1' => ['(gt)3', '(lt)10']])->getQuery()->wheres;
            $this->assertCount(2, $wheres);
            $this->assertEquals('Basic', $wheres[0]['type']);
            $this->assertEquals('3', $wheres[0]['value']);
            $this->assertEquals('>', $wheres[0]['operator']);
            $this->assertEquals('field1', $wheres[0]['column']);
            $this->assertEquals('Basic', $wheres[1]['type']);
            $this->assertEquals('10', $wheres[1]['value']);
            $this->assertEquals('<', $wheres[1]['operator']);
            $this->assertEquals('field1', $wheres[0]['column']);

            $wheres = (array)TestModelWithSearchableMethod::filtered(['field1' => ['100%', '!10']])->getQuery()->wheres;
            $this->assertCount(2, $wheres);
            $this->assertEquals('Basic', $wheres[0]['type']);
            $this->assertEquals('100%', $wheres[0]['value']);
            $this->assertEquals('like', $wheres[0]['operator']);
            $this->assertEquals('field1', $wheres[0]['column']);
            $this->assertEquals('Basic', $wheres[1]['type']);
            $this->assertEquals('10', $wheres[1]['value']);
            $this->assertEquals('<>', $wheres[1]['operator']);
            $this->assertEquals('field1', $wheres[1]['column']);

            $wheres = (array)TestModelWithSearchableMethod::filtered(['field1' => ['20%', '!2013%']])->getQuery()->wheres;
            $this->assertCount(2, $wheres);
            $this->assertEquals('Basic', $wheres[0]['type']);
            $this->assertEquals('20%', $wheres[0]['value']);
            $this->assertEquals('like', $wheres[0]['operator']);
            $this->assertEquals('field1', $wheres[0]['column']);
            $this->assertEquals('Basic', $wheres[1]['type']);
            $this->assertEquals('2013%', $wheres[1]['value']);
            $this->assertEquals('not like', $wheres[1]['operator']);
            $this->assertEquals('field1', $wheres[1]['column']);
        });

        $this->specify("mode is recognized and applied correctly", function() {
            $this->assertCount(2, (array)TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3, 'mode' => 'and'])->getQuery()->wheres);
            foreach (TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3, 'mode' => 'and'])->getQuery()->wheres as $where) {
                $this->assertEquals(Constraint::MODE_AND, $where['boolean']);
            }

            $this->assertCount(2, (array)TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3, 'mode' => 'or'])->getQuery()->wheres);
            foreach (TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3, 'mode' => 'or'])->getQuery()->wheres as $where) {
                $this->assertEquals(Constraint::MODE_OR, $where['boolean']);
            }
        });

        $this->specify("AND mode is the default value if no mode or invalid mode is provided", function() {
            $this->assertCount(2, (array)TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3, 'mode' => 'invalid'])->getQuery()->wheres);
            foreach (TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3, 'mode' => 'invalid'])->getQuery()->wheres as $where) {
                $this->assertEquals(Constraint::MODE_AND, $where['boolean']);
            }

            $this->assertCount(2, (array)TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3])->getQuery()->wheres);
            foreach (TestModelWithSearchableMethod::filtered(['field1' => 5, 'field2' => 3])->getQuery()->wheres as $where) {
                $this->assertEquals(Constraint::MODE_AND, $where['boolean']);
            }
        });

        $this->specify('getSearchableAttribues is not required, if $searchable property exists', function() {
            $this->assertCount(1, (array)TestModelWithSearchableProperty::filtered(['field1' => 5])->getQuery()->wheres);
            $this->assertCount(2, (array)TestModelWithSearchableProperty::filtered(['field1' => 5, 'field2' => 3])->getQuery()->wheres);
        });

        $this->specify('model must implement getSearchableAttributes() or have $searchable property', function() {
            TestModel::filtered(['field1' => 5]);
        }, ['throws' => new RuntimeException]);

        $this->specify('* in searchable field list makes all fields searchable', function() {
            $this->assertCount(1, (array)TestModelWithAllFieldsSearchable::filtered(['field1' => 5])->getQuery()->wheres);
            $this->assertCount(2, (array)TestModelWithAllFieldsSearchable::filtered(['field1' => 5, 'field42' => 3])->getQuery()->wheres);
        });
    }
}
