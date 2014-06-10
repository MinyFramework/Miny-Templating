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
use Modules\Templating\Compiler\Tag;

class TagNode extends Node
{
    /**
     * @var Tag
     */
    private $tag;

    /**
     * @param Tag   $tag
     * @param array $data
     */
    public function __construct(Tag $tag, array $data = array())
    {
        $this->tag  = $tag;
        $this->setData($data);
    }

    /**
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    public function compile(Compiler $compiler)
    {
        $this->tag->compile($compiler, $this);
    }
}
