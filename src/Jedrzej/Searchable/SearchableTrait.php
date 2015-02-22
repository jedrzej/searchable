<?php namespace Jedrzej\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;

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
        foreach ($query as $field => $value) {
            if ($this->isFieldSearchable($builder, $field)) {
                $constraints[$field] = Constraint::make($value);
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
     * @param Builder      $builder     query builder
     * @param Constraint[] $constraints constraints
     */
    protected function applyConstraints(Builder $builder, array $constraints)
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
     * @param Builder    $builder    query builder
     * @param string     $field      field on which constraint is applied
     * @param Constraint $constraint constraint
     *
     * @return bool true if constraint was intercepted by model's method
     */
    protected function callInterceptor(Builder $builder, $field, Constraint $constraint)
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
}
