<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

class OperatorCollection
{
    private $operators;
    private $empty;

    public function __construct()
    {
        $this->operators = array();
        $this->empty = true;
    }

    public function isEmpty()
    {
        return $this->empty;
    }

    public function exists(Operator $operator)
    {
        return in_array($operator, $this->operators, true);
    }

    public function addOperator(Operator $operator)
    {
        $this->empty = false;
        $symbol = $operator->operators();
        if (is_array($symbol)) {
            foreach ($symbol as $op_symbol) {
                $this->operators[$op_symbol] = $operator;
            }
        } else {
            $this->operators[$symbol] = $operator;
        }
    }

    public function isOperator($operator)
    {
        return isset($this->operators[$operator]);
    }

    public function getOperator($sign)
    {
        return $this->operators[$sign];
    }

    public function getSymbols()
    {
        return array_keys($this->operators);
    }
}