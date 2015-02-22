<?php

use Codeception\Specify;
use Codeception\TestCase\Test;
use Jedrzej\Searchable\Constraint;

class ConstraintTest extends Test
{
    use Specify;

    public function testNegation() {
        $this->specify("exclamation mark as first character means negation", function() {
            $this->assertTrue(Constraint::make('!abc')->isNegation());
            $this->assertTrue(Constraint::make('!(gt)5')->isNegation());
            $this->assertTrue(Constraint::make('!a%')->isNegation());
            $this->assertTrue(Constraint::make('!%a%')->isNegation());
            $this->assertTrue(Constraint::make('!ab,bc')->isNegation());
        });

        $this->specify("no exclamation mark as first character means no negation", function() {
            $this->assertFalse(Constraint::make('abc')->isNegation());
            $this->assertFalse(Constraint::make('(gt)5')->isNegation());
            $this->assertFalse(Constraint::make('a%')->isNegation());
            $this->assertFalse(Constraint::make('%a%')->isNegation());
            $this->assertFalse(Constraint::make('ab,bc')->isNegation());
        });

        $this->specify("only first exclamation mark is discarded", function() {
            $this->assertEquals('!abc', Constraint::make('!!abc')->getValue());
            $this->assertEquals('a!bc', Constraint::make('!a!bc')->getValue());
        });

        $this->specify("only first exclamation mark is interpreted as negation", function() {
            $this->assertFalse(Constraint::make('a!bc')->isNegation());
            $this->assertFalse(Constraint::make('(gt)!5')->isNegation());
        });
    }

    public function testOperator() {
        $this->specify("comparison operators are recognized", function() {
            $this->assertEquals(Constraint::OPERATOR_GREATER, Constraint::make('(gt)5')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_GREATER_EQUAL, Constraint::make('(ge)5')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_LESS, Constraint::make('(lt)5')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_LESS_EQUAL, Constraint::make('(le)5')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_LESS_EQUAL, Constraint::make('!(gt)5')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_LESS, Constraint::make('!(ge)5')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_GREATER_EQUAL, Constraint::make('!(lt)5')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_GREATER, Constraint::make('!(le)5')->getOperator());
        });

        $this->specify("comparison operators take precedence over constraints provided in the value", function() {
            $this->assertEquals(Constraint::OPERATOR_GREATER, Constraint::make('(gt)ab,bc')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_GREATER, Constraint::make('(gt)%ab%')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_GREATER, Constraint::make('(gt)%ab')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_GREATER, Constraint::make('(gt)ab%')->getOperator());
        });

        $this->specify("like operator is recognized", function() {
            $this->assertEquals(Constraint::OPERATOR_LIKE, Constraint::make('%a%')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_LIKE, Constraint::make('a%')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_LIKE, Constraint::make('%a')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_NOT_LIKE, Constraint::make('!%a%')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_NOT_LIKE, Constraint::make('!a%')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_NOT_LIKE, Constraint::make('!%a')->getOperator());
        });

        $this->specify("like operators take precedence over constraints provided in the value", function() {
            $this->assertEquals(Constraint::OPERATOR_LIKE, Constraint::make('%ab,bc%')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_LIKE, Constraint::make('%ab,bc')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_LIKE, Constraint::make('ab,bc%')->getOperator());
        });

        $this->specify("in and equals operators are recognized", function() {
            $this->assertEquals(Constraint::OPERATOR_EQUAL, Constraint::make('abbc')->getOperator());
            $this->assertEquals(Constraint::OPERATOR_IN, Constraint::make('ab,bc')->getOperator());
        });
    }

    public function testValue() {
        $this->specify("value is recognized correctly", function() {
            $this->assertEquals('abc', Constraint::make('abc')->getValue());
            $this->assertEquals('abc', Constraint::make('(ge)abc')->getValue());
            $this->assertEquals('abc', Constraint::make('!abc')->getValue());
            $this->assertEquals('abc', Constraint::make('!(ge)abc')->getValue());
            $this->assertEquals(['ab', 'bc'], Constraint::make('ab,bc')->getValue());
            $this->assertEquals(['ab', 'bc'], Constraint::make('!ab,bc')->getValue());
            $this->assertEquals('ab,bc', Constraint::make('(gt)ab,bc')->getValue());
            $this->assertEquals('%ab,bc%', Constraint::make('%ab,bc%')->getValue());
            $this->assertEquals('%ab,bc', Constraint::make('%ab,bc')->getValue());
            $this->assertEquals('ab,bc%', Constraint::make('ab,bc%')->getValue());
            $this->assertEquals('ab,bc', Constraint::make('!(gt)ab,bc')->getValue());
            $this->assertEquals('%ab,bc%', Constraint::make('!%ab,bc%')->getValue());
            $this->assertEquals('%ab,bc', Constraint::make('!%ab,bc')->getValue());
            $this->assertEquals('ab,bc%', Constraint::make('!ab,bc%')->getValue());
        });
    }
}
