<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Closure;
use Modules\Templating\Compiler\Exceptions\ParseException;
use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Compiler\Nodes\ClassNode;
use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\FileNode;
use Modules\Templating\Compiler\Nodes\RootNode;
use Modules\Templating\Compiler\Nodes\PrintNode;
use Modules\Templating\Environment;

class Parser
{
    /**
     * @var Tag[]
     */
    private $tags;

    /**
     * @var ExpressionParser
     */
    private $expressionParser;

    /**
     * @var Environment
     */
    private $environment;

    private $level = 0;

    /**
     * @var FileNode
     */
    private $fileNode;
    /**
     * @var ClassNode
     */
    private $classNode;

    private $block;
    private $blocks = array();

    public function __construct(Environment $environment, ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
        $this->tags             = $environment->getTags();
        $this->environment      = $environment;
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    private function parseToken(Stream $stream, RootNode $root)
    {
        $token = $stream->current();
        $value = $token->getValue();

        switch ($token->getType()) {
            case Token::TEXT:
                $node = new PrintNode();
                $node->addChild(new DataNode($value), 'expression');
                break;

            case Token::TAG:
                if (!isset($this->tags[$value])) {
                    $line = $token->getLine();
                    throw new ParseException("Unknown {$value} tag", $line);
                }
                $stream->next();

                $node = $this->tags[$value]->parse($this, $stream);
                break;

            default:
                $type = $token->getTypeString();
                $line = $token->getLine();
                throw new ParseException("Unexpected {$type} ({$value}) token", $line);
        }
        if (isset($node)) {
            $node->setParent($root);
        }

        return $stream->next();
    }

    public function inMainScope()
    {
        return $this->level === 1;
    }

    public function parseTemplate(Stream $stream, $className)
    {
        $fileNode = new FileNode();

        $this->fileNode  = $fileNode;
        $this->classNode = $fileNode->addChild(
            new ClassNode(
                $this->environment,
                $className
            )
        );
        $this->classNode->addChild($this->parse($stream), '__main_template_block');

        return $fileNode;
    }

    public function getCurrentClassNode()
    {
        return $this->classNode;
    }

    public function setCurrentClassNode(ClassNode $classNode)
    {
        $this->classNode = $classNode;
    }

    public function getCurrentFileNode()
    {
        return $this->fileNode;
    }

    public function parse(Stream $stream, Closure $endCondition = null)
    {
        ++$this->level;
        $root  = new RootNode();
        $token = $stream->next();

        if ($endCondition) {
            while (!$endCondition($token)) {
                $token = $this->parseToken($stream, $root);
            }
        } else {
            while (!$token->test(Token::EOF)) {
                $token = $this->parseToken($stream, $root);
            }
        }
        --$this->level;

        return $root;
    }

    /**
     * @param Stream $stream
     *
     * @return Node
     */
    public function parseExpression(Stream $stream)
    {
        return $this->expressionParser->parse($stream);
    }

    public function enterBlock($blockName)
    {
        $this->blocks[] = $this->block;
        $this->block    = $blockName;
    }

    public function leaveBlock()
    {
        $this->block = array_pop($this->blocks);
    }

    public function getCurrentBlock()
    {
        if (!isset($this->block)) {
            throw new ParseException('Currently not in a block.');
        }

        return $this->block;
    }
}
