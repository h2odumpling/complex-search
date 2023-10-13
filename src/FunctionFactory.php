<?php

namespace H2o\ComplexSearch;

class FunctionFactory
{
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }
        return 0;
    }

    public function add(...$arguments)
    {
        return array_reduce($arguments, function ($a, $b) {
            return $a === null ? $b : $a + $b;
        });
    }

    public function sub(...$arguments)
    {
        return array_reduce($arguments, function ($a, $b) {
            return $a === null ? $b : $a - $b;
        });
    }

    public function mul(...$arguments)
    {
        return array_reduce($arguments, function ($a, $b) {
            return $a === null ? $b : $a * $b;
        });
    }

    public function div(...$arguments)
    {
        return array_reduce($arguments, function ($a, $b) {
            return $a === null ? $b : ($b == 0 ? 0 : $a / $b);
        });
    }

    public function sum(&$filed, $value)
    {
        $filed += $value;
    }

    public function max(&$filed, $value)
    {
        if ($filed < $value) {
            $filed = $value;
        }
    }

    public function min(&$filed, $value)
    {
        if ($filed > $value) {
            $filed = $value;
        }
    }

    public function round($field, $len)
    {
        return round($field, $len);
    }
}