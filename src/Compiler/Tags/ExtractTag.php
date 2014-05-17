<?php

/**
 * This file is part of the Miny framework.
 * (c) DÃÂ¡niel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;

class ExtractTag extends Tag
{

    public function getTag()
    {
        return 'extract';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $keys = $parser->parseExpression($stream);
        $stream->expectCurrent(Token::IDENTIFIER, 'from');
        $source = $parser->parseExpression($stream);

        return new TagNode($this, array(
            'source' => $source,
            'keys'   => $keys
        ));
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $data = $node->getData();
        $compiler->indented('$this->extract(')
            ->compileNode($data['source'])
            ->add(', ')
            ->compileNode($data['keys'])
            ->add(');');
    }
}
