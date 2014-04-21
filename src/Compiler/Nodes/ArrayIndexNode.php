<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Node;

class ArrayIndexNode extends Node
{
    private $identifier;
    private $key;

    public function __construct(Node $identifier, Node $key)
    {
        $this->identifier = $identifier;
        $this->key        = $key;
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->compileNode($this->identifier)
            ->add('[')
            ->compileNode($this->key)
            ->add(']');
    }
}
