<?php namespace Jedrzej\Searchable;

use Illuminate\Database\Eloquent\Builder;

class Constraint
{
    const OPERATOR_EQUALS = '=';

    const OPERATOR_GREATER_EQUAL = '>=';

    const OPERATOR_LESS_EQUAL = '<=';

    const OPERATOR_IN = 'In';

    protected $operator;

    protected $value;

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Creates constraint object for given filter
     *
     * @param string $filter query key
     * @param string $value  query value
     *
     * @return Constraint
     */
    public static function make($filter, $value)
    {
        $value = static::prepareValue($value);

        if (preg_match('/_from$/', $filter)) {
            return new static(static::OPERATOR_GREATER_EQUAL, $value);
        } else {
            if (preg_match('/_to$/', $filter)) {
                return new static(static::OPERATOR_LESS_EQUAL, $value);
            } else {
                $operator = is_array($value) ? static::OPERATOR_IN
                    : static::OPERATOR_EQUALS;

                return new static($operator, $value);
            }
        }
    }

    /**
     * Applies constraint to query
     *
     * @param Builder $builder query builder
     * @param         $field   field name
     */
    public function apply(Builder $builder, $field)
    {
        if ($this->operator == Constraint::OPERATOR_IN) {
            $builder->whereIn($field, $this->value);
        } else {
            $builder->where($field, $this->operator,
                $this->value);
        }
    }

    /**
     * @param string $operator operator
     * @param mixed  $value    value
     */
    protected function __construct($operator, $value)
    {
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     *  Cleans value and converts to array if needed
     *
     * @param string $value value
     *
     * @return array|string
     */
    protected static function prepareValue($value)
    {
        // cleanup
        $value = trim($value, ", \t\n\r\0\x0B");

        // convert value to array if it cotains commas
        if (strpos($value, ',') !== false) {
            $value = preg_split('/,/', $value);

            return $value;
        }

        return $value;
    }
}
