<?php

namespace STS\HubSpot\Api;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class Filter
{
    protected string $property;
    protected string $operator;
    protected $value;
    protected $endValue;

    public function __construct($property, $operator, $value = null, $endValue = null)
    {
        $this->property = $property;
        $this->endValue = $endValue;

        if ($value === null && !in_array($this->translateOperator($operator), ['HAS_PROPERTY', 'NOT_HAS_PROPERTY'])) {
            $this->operator = "EQ";
            $this->value = $operator;
        } else {
            $this->operator = $this->translateOperator($operator);
            $this->value = $value;
        }
    }

    public function toArray()
    {
        if ($this->operator === 'BETWEEN') {
            return [
                'propertyName' => $this->property,
                'operator'     => $this->operator,
                'highValue'    => $this->value[0],
                'value'        => $this->value[1],
            ];
        }

        if ($this->operator === 'IN') {
            return [
                'propertyName' => $this->property,
                'operator'     => $this->operator,
                'values'       => $this->value,
            ];
        }

        return array_filter([
            'propertyName' => $this->property,
            'operator'     => $this->operator,
            'value'        => $this->cast($this->value)
        ]);
    }

    protected function cast($value = null)
    {
        if ($value instanceof Carbon) {
            return $value->timestamp;
        }

        return $value;
    }

    protected function translateOperator($operator): string
    {
        return Arr::get([
            '='          => 'EQ',
            '!='         => 'NEQ',
            '<'          => 'LT',
            '<='         => 'LTE',
            '>'          => 'GT',
            '>='         => 'GTE',
            'exists'     => 'HAS_PROPERTY',
            'not exists' => 'NOT_HAS_PROPERTY',
            'like'       => 'CONTAINS_TOKEN',
            'not like'   => 'NOT_CONTAINS_TOKEN'
        ], strtolower($operator), $operator);
    }
}