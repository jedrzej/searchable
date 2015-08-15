<?php

use Illuminate\Database\Eloquent\Builder;
use Jedrzej\Searchable\Constraint;

class TestModelWithSearchableMethod extends TestModel
{
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
}