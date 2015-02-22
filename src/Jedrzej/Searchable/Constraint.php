<?php namespace Jedrzej\Searchable;

use Illuminate\Database\Eloquent\Builder;

class Constraint
{
    const OPERATOR_EQUAL = '=';
    const OPERATOR_NOT_EQUAL = '<>';

    const OPERATOR_GREATER_EQUAL = '>=';
    const OPERATOR_GREATER = '>';

    const OPERATOR_LESS_EQUAL = '<=';
    const OPERATOR_LESS = '<';

    const OPERATOR_LIKE = 'like';
    const OPERATOR_NOT_LIKE = 'not like';

    const OPERATOR_IN = 'in';
    const OPERATOR_NOT_IN = 'not in';

    protected $operator;

    protected $value;

    protected $is_negation;

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
     * @return boolean
     */
    public function isNegation()
    {
        return $this->is_negation;
    }

    /**
     * Creates constraint object for given filter
     *
     * @param string $value query value
     *
     * @return Constraint
     */
    public static function make($value)
    {
        $value = static::prepareValue($value);
        $is_negation = static::parseIsNegation($value);
        list($operator, $value) = static::parseOperatorAndValue($value, $is_negation);

        return new static($operator, $value, $is_negation);
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
        } elseif ($this->operator == Constraint::OPERATOR_NOT_IN) {
            $builder->whereNotIn($field, $this->value);
        } else {
            $builder->where($field, $this->operator, $this->value);
        }
    }

    /**
     * @param string $operator operator
     * @param mixed  $value    value
     * @param bool   $is_negation
     */
    protected function __construct($operator, $value, $is_negation = false)
    {
        $this->operator = $operator;
        $this->value = $value;
        $this->is_negation = $is_negation;
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
        return trim($value, ", \t\n\r\0\x0B");
    }

    /**
     * Check if query constraint is negated
     *
     * @param string $value value
     *
     * @return bool
     */
    protected static function parseIsNegation(&$value)
    {
        if (preg_match('/^!/', $value)) {
            $value = preg_replace('/^!/', '', $value);
            return true;
        }

        return false;
    }

    /**
     * Parse query parameter and get operator and value
     *
     * @param string $value
     * @param bool $is_negation
     *
     * @return array
     */
    protected static function parseOperatorAndValue($value, $is_negation)
    {
        if (preg_match('/^\((gt|ge|lt|le)\)(.+)$/', $value, $match)) {
            switch ($match[1]) {
                case 'gt':
                    $operator = $is_negation ? static::OPERATOR_LESS_EQUAL : static::OPERATOR_GREATER;
                    break;
                case 'ge':
                    $operator = $is_negation ? static::OPERATOR_LESS : static::OPERATOR_GREATER_EQUAL;
                    break;
                case 'lt':
                    $operator = $is_negation ? static::OPERATOR_GREATER_EQUAL : static::OPERATOR_LESS;
                    break;
                case 'le':
                    $operator = $is_negation ? static::OPERATOR_GREATER : static::OPERATOR_LESS_EQUAL;
                    break;
            }

            $value = $match[2];

            return [$operator, $value];
        }

        if (preg_match('/(^%.+)|(.+%$)/', $value)) {
            return [$is_negation ? static::OPERATOR_NOT_LIKE : static::OPERATOR_LIKE, $value];
        }

        if (strpos($value, ',') !== false) {
            $value = preg_split('/,/', $value);
        }

        return $is_negation ?
            [is_array($value) ? static::OPERATOR_NOT_IN : static::OPERATOR_NOT_EQUAL, $value]
            :
            [is_array($value) ? static::OPERATOR_IN : static::OPERATOR_EQUAL, $value];
    }
}
