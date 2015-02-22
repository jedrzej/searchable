<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Jedrzej\Searchable\Constraint;
use Jedrzej\Searchable\SearchableTrait;

class TestModel extends Model
{
    use SearchableTrait;

    /**
     * Returns list of searchable fields
     *
     * @return array
     */
    public function getSearchableAttributes()
    {
        return ['field1', 'field2'];
    }

    public function processField2Filter(Builder $builder, Constraint $constraint) {
        if ($constraint->getOperator() == Constraint::OPERATOR_IN && is_array($constraint->getValue()) && $constraint->getValue()) {
            $builder->where('field2', $constraint->getValue()[0]);

            return true;
        }
    }

    protected function newBaseQueryBuilder()
    {
        return new TestBuilder;
    }
}