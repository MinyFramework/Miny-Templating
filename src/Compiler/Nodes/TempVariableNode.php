<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;

class TempVariableNode extends IdentifierNode
{
    public function compile(Compiler $compiler)
    {
        $compiler
            ->add('$' . $this->getName());
    }
}
