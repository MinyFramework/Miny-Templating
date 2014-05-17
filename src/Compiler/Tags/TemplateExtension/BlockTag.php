<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\TemplateExtension;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Node;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;

class BlockTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'block';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $data = $node->getData();
        $compiler->startTemplate($data['template']);
        /** @var $body Node */
        $body = $data['body'];
        $body->compile($compiler);
        $template = $compiler->endTemplate();

        $compiler->indented('echo $this->%s();', $template);
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this, array(
            'template' => $stream->expect(Token::IDENTIFIER)->getValue()
        ));
        $stream->expect(Token::EXPRESSION_END);

        $bodyNode = $parser->parse(
            $stream,
            function (Stream $stream) {
                return $stream->next()->test(Token::TAG, 'endblock');
            }
        );
        $bodyNode->setParent($node);
        $node->addData('body', $bodyNode);

        return $node;
    }
}
