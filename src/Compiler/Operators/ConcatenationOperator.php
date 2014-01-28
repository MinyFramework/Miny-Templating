<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;

class ConcatenationOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return '~';
    }

    protected function compileOperator()
    {
        return ' . ';
    }
}
