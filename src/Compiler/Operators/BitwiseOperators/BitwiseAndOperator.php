<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\BitwiseOperators;

use Modules\Templating\Compiler\Operators\SimpleBinaryOperator;

class BitwiseAndOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return 'b-and';
    }

    protected function compileOperator()
    {
        return ' & ';
    }
}