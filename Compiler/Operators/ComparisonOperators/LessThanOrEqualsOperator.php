<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\ComparisonOperators;

use Modules\Templating\Compiler\Operators\ComparisonOperator;

class LessThanOrEqualsOperator extends ComparisonOperator
{

    public function operators()
    {
        return array('<=', 'is less than or equals');
    }

    public function compileSymbol()
    {
        return ' <= ';
    }
}
