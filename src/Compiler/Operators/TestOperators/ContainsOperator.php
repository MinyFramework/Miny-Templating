<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

class ContainsOperator extends Operator
{

    public function operators()
    {
        return array('in', 'contains');
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('$this->isIn(')
            ->compileNode($node->getOperand(OperatorNode::OPERAND_LEFT))
            ->add(', ')
            ->compileNode($node->getOperand(OperatorNode::OPERAND_RIGHT))
            ->add(')');
    }
}
