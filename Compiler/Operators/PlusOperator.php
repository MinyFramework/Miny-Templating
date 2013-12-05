<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Token;

class PlusOperator extends Operator
{

    public function operators()
    {
        return '+';
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();

        $binary = array(
            Token::EXPRESSION_END,
            Token::ARGUMENT_LIST_END,
            Token::IDENTIFIER,
            Token::LITERAL,
        );
        if ($stream->test($binary)) {
            return false;
        }

        $unary = array(
            Token::EXPRESSION_START,
            Token::ARGUMENT_LIST_START,
            array(Token::OPERATOR, array('^')),
        );
        if ($stream->test($unary)) {
            $parser->pushToken(Token::OPERATOR, $operator);
            $stream->expect(Token::LITERAL, 'is_numeric');
            $stream->expect(Token::IDENTIFIER);
            $stream->expect(Token::EXPRESSION_START)->then(Token::STRING, null, 1, true);
            return true;
        }
        return false;
    }
}
