<?php

use Illuminate\Database\Eloquent\Builder;
use Jedrzej\Searchable\Constraint;

class TestModelWithRelations extends TestModel
{
    protected $searchable = ['relationA:field'];

    public function relationA() {
        return $this->belongsTo(TestModel::class);
    }

    public function relationB() {
        return $this->belongsTo(TestModel::class);
    }

    public function processRelationA_FieldFilter(Builder $builder, Constraint $constraint) {
        $builder->where('relation_a_field', $constraint->getValue());

        return true;
    }
}