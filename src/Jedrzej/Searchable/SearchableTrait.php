<?php namespace Jedrzej\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Input;

trait SearchableTrait
{
    /**
     * Should return list of searchable fields
     *
     * @return array
     */
    abstract public function getSearchableAttributes();

    /**
     * Applies filters
     *
     * @param Builder $builder query builder
     * @param array   $query   query parameters to use for search - Input::all() is used by default
     */
    public function scopeFiltered(Builder $builder, array $query = [])
    {
        $query = $query ?: Input::all();

        $constraints = $this->getConstraints($builder, $query);
        $this->applyConstraints($builder, $constraints);
    }

    /**
     * Builds search constraints based on model's searchable fields and query parameters
     *
     * @param Builder $builder query builder
     * @param array   $query   query parameters
     *
     * @return array
     */
    protected function getConstraints(Builder $builder, array $query)
    {
        $constraints = [];
        foreach ($query as $filter => $value) {
            $field = $this->getField($filter);
            if ($this->isFieldSearchable($builder, $field)) {
                $constraints[$field] = Constraint::make($filter, $value);
            }
        }

        return $constraints;
    }

    /**
     * Check if field is searchable for given model
     *
     * @param Builder $builder query builder
     * @param string  $field   field name
     *
     * @return bool
     */
    protected function isFieldSearchable(Builder $builder, $field)
    {
        $searchable = $builder->getModel()->getSearchableAttributes();

        return in_array($field, $searchable);
    }

    /**
     * Applies constraints to query, allowing model to overwrite any of them
     *
     * @param Builder $builder     query builder
     * @param         $constraints constraints
     */
    protected function applyConstraints(Builder $builder, $constraints)
    {
        foreach ($constraints as $field => $constraint) {

            // let model handle the constraint if it has the interceptor
            if ($this->callInterceptor($builder, $field, $constraint)) {
                continue;
            }

            $constraint->apply($builder, $field);
        }
    }

    /**
     * Calls constraint interceptor on model
     *
     * @param Builder $builder    query builder
     * @param         $field      field on which constraint is applied
     * @param         $constraint constraint
     *
     * @return bool true if constraint was intercepted by model's method
     */
    protected function callInterceptor(Builder $builder, $field, $constraint)
    {
        $model = $builder->getModel();
        $interceptor = sprintf('process%sFilter', Str::studly($field));

        if (method_exists($model, $interceptor)) {
            if ($model->$interceptor($builder, $constraint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets field name from filter
     *
     * @param string $filter query key
     *
     * @return mixed
     */
    protected function getField($filter)
    {
        $field = preg_replace('/_(from|to)$/', '', $filter);

        return $field;
    }
}
