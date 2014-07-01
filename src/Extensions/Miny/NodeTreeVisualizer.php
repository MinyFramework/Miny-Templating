<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Extensions\Miny;

use Minty\Compiler\Node;
use Minty\Compiler\Nodes\DataNode;
use Minty\Compiler\Nodes\IdentifierNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\RootNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\NodeVisitor;
use Miny\Log\AbstractLog;
use Miny\Log\Log;

/**
 * Class NodeTreeVisualizer
 *
 * The main goal of this class is to provide a tool to print out the Abstract Syntax Tree
 * for debug purposes.
 */
class NodeTreeVisualizer extends NodeVisitor
{
    private $level = 0;

    /**
     * @var AbstractLog
     */
    private $log;

    public function __construct(AbstractLog $log)
    {
        $this->log = $log;
    }

    public function getPriority()
    {
        //Set a low priority so that the optimization results are printed
        return 100;
    }

    public function enterNode(Node $node)
    {
        $str = $this->nodeToString($node);

        $this->log->write(Log::DEBUG, 'NodeTreeVisualizer', $str);

        ++$this->level;
    }

    public function leaveNode(Node $node)
    {
        --$this->level;

        return $node;
    }

    private function nodeToString(Node $node)
    {
        $string = str_repeat('|-', $this->level);
        $string .= get_class($node);

        if ($node instanceof RootNode) {
            $childCount = count($node->getChildren());
            $string .= " ({$childCount})";
        } elseif ($node instanceof TagNode) {
            $string .= " ({$node->getTag()->getTag()})";
        } elseif ($node instanceof OperatorNode) {
            $symbols = $node->getOperator()->operators();
            if (is_array($symbols)) {
                $symbols = implode(', ', $symbols);
            }
            $string .= " ({$symbols})";
        } elseif ($node instanceof IdentifierNode) {
            //Variable, name or function
            $string .= " ({$node->getData('name')})";
        } elseif ($node instanceof DataNode) {
            $string .= " ({$node->stringify()})";
        }

        return $string;
    }
}
