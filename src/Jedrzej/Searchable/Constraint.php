<?php namespace Jedrzej\Searchable;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

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

    const OPERATOR_NULL = 'is null';
    const OPERATOR_NOT_NULL = 'is not null';

    const MODE_AND = 'and';
    const MODE_OR = 'or';

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
     * @return string
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
     * Creates constraint object for given filter.
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
     * Applies constraint to query.
     *
     * @param Builder $builder query builder
     * @param string $field field name
     * @param string $mode determines how constraint is added to existing query ("or" or "and")
     */
    public function apply(Builder $builder, $field, $mode = Constraint::MODE_AND)
    {
        if ($this->isRelation($field)) {
            list($relation, $field) = $this->splitRelationField($field);
            if (static::parseIsNegation($relation)) {
                $builder->whereDoesntHave($relation, function (Builder $builder) use ($field, $mode) {
                    $this->doApply($builder, $field, $mode);
                });
            } else {
                $builder->whereHas($relation, function (Builder $builder) use ($field, $mode) {
                    $this->doApply($builder, $field, $mode);
                });
            }
        } else {
            $this->doApply($builder, $field, $mode);
        }
    }

    /**
     * Check if given field contains relation name
     *
     * @param string $field field name
     * @return bool true if field contains relation name
     */
    protected function isRelation($field)
    {
        return strpos($field, ':') !== false;
    }

    /**
     * Splits given field name containing relation name into relation name and field name
     * @param string $field field name
     * @return array relation name at index 0, field name at index 1
     */
    protected function splitRelationField($field)
    {
        $parts = explode(':', $field);
        $partsCount = count($parts);

        return [implode('.', array_slice($parts, 0, $partsCount - 1)), $parts[$partsCount - 1]];
    }

    /**
     * Applies non-relation constraint to query.
     *
     * @param Builder $builder query builder
     * @param string $field field name
     * @param string $mode determines how constraint is added to existing query ("or" or "and")
     */
    protected function doApply(Builder $builder, $field, $mode = Constraint::MODE_AND)
    {
        if ($this->operator == Constraint::OPERATOR_IN) {
            $method = $mode != static::MODE_OR ? 'whereIn' : 'orWhereIn';
            $builder->$method($field, $this->value);
        } elseif ($this->operator == Constraint::OPERATOR_NOT_IN) {
            $method = $mode != static::MODE_OR ? 'whereNotIn' : 'orWhereNotIn';
            $builder->$method($field, $this->value);
        } elseif ($this->operator == Constraint::OPERATOR_NOT_NULL) {
            $method = $mode != static::MODE_OR ? 'whereNotNull' : 'orWhereNotNull';
            $builder->$method($field);
        } elseif ($this->operator == Constraint::OPERATOR_NULL) {
            $method = $mode != static::MODE_OR ? 'whereNull' : 'orWhereNull';
            $builder->$method($field);
        } else {
            $method = $mode != static::MODE_OR ? 'where' : 'orWhere';
            $builder->$method($field, $this->operator, $this->value);
        }
    }

    /**
     * @param string $operator operator
     * @param string $value value
     * @param bool $is_negation
     */
    protected function __construct($operator, $value, $is_negation = false)
    {
        $this->operator = $operator;
        $this->value = $value;
        $this->is_negation = $is_negation;
    }

    /**
     *  Cleans value and converts to array if needed.
     *
     * @param string $value value
     *
     * @return string
     */
    protected static function prepareValue($value)
    {
        return trim($value, ", \t\n\r\0\x0B");
    }

    /**
     * Check if query constraint is negated.
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
     * Parse query parameter and get operator and value.
     *
     * @param string $value
     * @param bool $is_negation
     *
     * @return string[]
     *
     * @throws InvalidArgumentException when unable to parse operator or value
     */
    protected static function parseOperatorAndValue($value, $is_negation)
    {
        if ($result = static::parseComparisonOperator($value, $is_negation)) {
            return $result;
        }

        if ($result = static::parseLikeOperator($value, $is_negation)) {
            return $result;
        }

        if ($result = static::parseNullOperator($value, $is_negation)) {
            return $result;
        }

        if ($result = static::parseEqualsInOperator($value, $is_negation)) {
            return $result;
        }

        throw new InvalidArgumentException(sprintf('Unable to parse operator or value from "%s"', $value));
    }

    /**
     * @param string $value
     * @param bool $is_negation
     *
     * @return string[]|false
     */
    protected static function parseComparisonOperator($value, $is_negation)
    {
        if (preg_match('/^\((gt|ge|lt|le)\)(.+)$/', $value, $match)) {
            $operator = null;
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

        return false;
    }

    /**
     * @param string $value
     * @param bool $is_negation
     *
     * @return string[]|false
     */
    protected static function parseNullOperator($value, $is_negation)
    {
        if ($value === '(null)') {
            $operator = $is_negation ? static::OPERATOR_NOT_NULL : static::OPERATOR_NULL;

            return [$operator, null];
        }

        return false;
    }

    /**
     * @param string $value
     * @param bool $is_negation
     *
     * @return string[]|false
     */
    protected static function parseLikeOperator($value, $is_negation)
    {
        if (preg_match('/(^%.+)|(.+%$)/', $value)) {
            return [$is_negation ? static::OPERATOR_NOT_LIKE : static::OPERATOR_LIKE, $value];
        }

        return false;
    }

    /**
     * @param string $value
     * @param bool $is_negation
     *
     * @return string[]
     */
    protected static function parseEqualsInOperator($value, $is_negation)
    {
        if (strpos($value, ',') !== false) {
            $value = preg_split('/,/', $value);
        }

        return $is_negation ?
            [is_array($value) ? static::OPERATOR_NOT_IN : static::OPERATOR_NOT_EQUAL, $value]
            :
            [is_array($value) ? static::OPERATOR_IN : static::OPERATOR_EQUAL, $value];
    }
}