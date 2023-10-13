<?php

namespace H2o\ComplexSearch;

class OperationNode
{
    public $fun;

    public $type;

    public $operator;

    public $belongTo;

    public $floor = 0;

    public $hasJump = false;

    /**
     * @var OperationNode
     */
    public $parentNode;

    public $values = array();

    public function __construct($fun, $belongTo)
    {
        $this->fun = $fun;
        $this->belongTo = $belongTo;

        $this->type($fun);
        $this->operator($fun);
    }

    public function createMultiTree(...$args)
    {
        $this->values = $args;

        foreach ($args as $arg) {
            if ($arg instanceof $this) {
                if ($arg->type === 2) {
                    $this->hasJump = true;
                }

                $this->floor = max($arg->floor + ($this->hasJump ? 1 : 0), $this->floor);
            } elseif ($arg === '#' && $this->floor < 2) {
                $this->floor += 1;
            }
        }
    }

    public function toString()
    {
        $values = $this->values;
        foreach ($values as &$value) {
            if ($value instanceof self) {
                $value = $value->toString();
            } elseif (is_array($value)) {
                $value = $value[1];
            }
        }
        return ($this->operator ? '' : $this->fun) . '(' . implode($this->operator ?: ',', $values) . ')';
    }

    private function type($fun)
    {
        if ($fun === 'add' || $fun === 'mul' || $fun === 'sub' || $fun === 'div') {
            $this->type = 1;
        } elseif ($fun === 'sum' || $fun === 'max' || $fun === 'min' || $fun === 'avg') {
            $this->type = 2;
        } else {
            $this->type = 0;
        }
    }

    private function operator($fun)
    {
        if ($fun === 'add') {
            $this->operator = '+';
        } elseif ($fun === 'sub') {
            $this->operator = '-';
        } elseif ($fun === 'mul') {
            $this->operator = '*';
        } elseif ($fun === 'div') {
            $this->operator = '/';
        } else {
            $this->operator = null;
        }
    }
}