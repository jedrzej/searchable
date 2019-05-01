<?php namespace Jedrzej\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use RuntimeException;

trait SearchableTrait
{
    public function getQueryModeParameterName()
    {
        return 'mode';
    }

    /**
     * Applies filters.
     *
     * @param Builder $builder query builder
     * @param array   $query   query parameters to use for search - Input::all() is used by default
     */
    public function scopeFiltered(Builder $builder, array $query = [])
    {
        $query = (array)($query ?: Input::all());

        $mode = $this->getQueryMode($query);

        $queries = [$mode => []];

        // We need to throw the filtered stuff out here.
        $query = $this->filterNonSearchableParameters($query);

        // IF the query param looks like `.*__or` or `.*__and` then put it into its own clause.
        foreach ($query as $index => $queryParam) {
            if (preg_match('/(.*)__(and|or)$/', $index, $matches)) {
                $theQuery = $matches[1];
                $theMode = $matches[2];

                if (!isset($queries[$theMode])) {
                    $queries[$theMode] = [];
                }

                $queries[$theMode][$theQuery] = $queryParam;
            } else {
                $queries[$mode][$index] = $queryParam;
            }
        }

        foreach ($queries as $mode => $newQuery) {
            // We need to throw the filtered stuff out here, too.
            $query = $this->filterNonSearchableParameters($newQuery);
            $constraints = $this->getConstraints($builder, $query);

            // The mode translates to an `where` group and a `orWhere` group.
            $method = $mode !== 'or' ? 'where' : 'orWhere';
            $builder->$method(function ($query) use ($constraints, $mode) {
                $this->applyConstraints($query, $constraints, $mode);
            });
        }
    }

    /**
     * Builds search constraints based on model's searchable fields and query parameters.
     *
     * @param Builder $builder query builder
     * @param array   $query   query parameters
     *
     * @return array
     */
    protected function getConstraints(Builder $builder, array $query)
    {
        $constraints = [];
        foreach ($query as $field => $values) {
            if ($this->isFieldSearchable($builder, $field)) {
                $constraints[$field] = $this->buildConstraints($values);
            }
        }

        return $constraints;
    }

    /**
     * Check if field is searchable for given model.
     *
     * @param Builder $builder query builder
     * @param string  $field   field name
     *
     * @return bool
     */
    protected function isFieldSearchable(Builder $builder, $field)
    {
        $searchable = $this->_getSearchableAttributes($builder);
        $notSearchable =  $this->_getNotSearchableAttributes($builder);

        $field = preg_replace('#^!#', '', $field);

        return !in_array($field, $notSearchable) && !in_array('*', $notSearchable) && (in_array($field, $searchable) || in_array('*', $searchable));
    }

    /**
     * Applies constraints to query, allowing model to overwrite any of them.
     *
     * @param Builder      $builder     query builder
     * @param Constraint[] $constraints constraints
     * @param string       $mode        determines how constraints are applied ("or" or "and")
     */
    protected function applyConstraints(Builder $builder, array $constraints, $mode = Constraint::MODE_AND)
    {
        foreach ($constraints as $field => $constraint) {
            if (is_array($constraint)) {
                foreach ($constraint as $single_constraint) {
                    $this->applyConstraint($builder, $field, $single_constraint, $mode);
                }
            } else {
                $this->applyConstraint($builder, $field, $constraint, $mode);
            }
        }
    }

    /**
     * Calls constraint interceptor on model.
     *
     * @param Builder    $builder query builder
     * @param string     $field field on which constraint is applied
     * @param Constraint $constraint constraint
     * @param string     $mode The mode.
     *
     * @return bool true if constraint was intercepted by model's method
     */
    protected function callInterceptor(Builder $builder, $field, Constraint $constraint, $mode = Constraint::MODE_AND)
    {
        $model = $builder->getModel();
        $interceptor = sprintf('process%sFilter', str_replace(':', '_', Str::studly($field)));

        if (method_exists($model, $interceptor)) {
            if ($model->$interceptor($builder, $constraint, $mode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build Constraint objects from given filter values
     *
     * @param string []|string
     *
     * @return Constraint[]|Constraint
     */
    protected function buildConstraints($values)
    {
        if (is_array($values)) {
            $constraints = [];
            foreach ($values as $value) {
                $constraints[] = Constraint::make($value);
            }
            return $constraints;
        } else {
            return Constraint::make($values);
        }
    }

    /**
     * Apply a single constraint - either directly or using model's interceptor
     *
     * @param Builder    $builder    query builder
     * @param string     $field      field name
     * @param Constraint $constraint constraint
     * @param string     $mode       determines how constraint is applied ("or" or "and")
     */
    protected function applyConstraint(Builder $builder, $field, $constraint, $mode = Constraint::MODE_AND)
    {
        // let model handle the constraint if it has the interceptor
        if (!$this->callInterceptor($builder, $field, $constraint, $mode)) {
            $constraint->apply($builder, $field, $mode);
        }
    }

    /**
     * Determines how constraints are applied ("or" or "and")
     *
     * @param array $query query parameters
     *
     * @return mixed
     */
    protected function getQueryMode(array $query = [])
    {
        return array_get($query, $this->getQueryModeParameterName(), Constraint::MODE_AND);
    }

    /**
     * @param Builder $builder
     * @return array list of searchable attributes
     */
    protected function _getSearchableAttributes(Builder $builder) {
        if (method_exists($builder->getModel(), 'getSearchableAttributes')) {
          return $builder->getModel()->getSearchableAttributes();
        }

        if (property_exists($builder->getModel(), 'searchable')) {
            return $builder->getModel()->searchable;
        }

        throw new RuntimeException(sprintf('Model %s must either implement getSearchableAttributes() or have $searchable property set', get_class($builder->getModel())));
    }

    /**
     * Removes parameters that have special meaning to the trait or related sortable/withable traits
     *
     * @param array $query query
     * @return array query without special parameters that model should not be searched on
     */
    protected function filterNonSearchableParameters(array $query) {
        $nonSearchableParameterNames = [$this->getQueryModeParameterName()];

        if (property_exists($this, 'withParameterName')) {
            $nonSearchableParameterNames[] = $this->withParameterName;
        }

        if (property_exists($this, 'sortParameterName')) {
            $nonSearchableParameterNames[] = $this->sortParameterName;
        }

        return array_except($query, $nonSearchableParameterNames);
    }

    /**
     * @param Builder $builder
     * @return array|mixed
     */
    protected function _getNotSearchableAttributes(Builder $builder)
    {
        if (method_exists($builder->getModel(), 'getNotSearchableAttributes')) {
            return $builder->getModel()->getNotSearchableAttributes();
        } else if (property_exists($builder->getModel(), 'notSearchable')) {
            return $builder->getModel()->notSearchable;
        }

        return [];
    }
}
