<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\ParseException;
use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Compiler\Nodes\ArrayIndexNode;
use Modules\Templating\Compiler\Nodes\ArrayNode;
use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operators\ConditionalOperator;

/**
 * Expression parser is based on the Shunting Yard algorithm by Edsger W. Dijkstra
 * @link http://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 */
class ExpressionParser
{
    /**
     * @var Operator[]
     */
    private $operator_stack;

    /**
     * @var Node[]
     */
    private $operand_stack;

    /**
     * @var Stream
     */
    private $stream;
    private $binary_test;
    private $unary_postfix_test;
    private $unary_prefix_test;
    private $binary_operators;
    private $unary_prefix_operators;
    private $unary_postfix_operators;

    public function __construct(Environment $environment)
    {
        $this->binary_operators        = $environment->getBinaryOperators();
        $this->unary_prefix_operators  = $environment->getUnaryPrefixOperators();
        $this->unary_postfix_operators = $environment->getUnaryPostfixOperators();

        $this->binary_test        = array($this->binary_operators, 'isOperator');
        $this->unary_prefix_test  = array($this->unary_prefix_operators, 'isOperator');
        $this->unary_postfix_test = array($this->unary_postfix_operators, 'isOperator');
    }

    public function parse(Stream $stream)
    {
        $this->operator_stack = array();
        $this->operand_stack  = array();
        $this->stream         = $stream;

        return $this->parseExpression(true);
    }

    public function parseArgumentList()
    {
        $arguments = array();

        $first = true;
        if (!$this->stream->nextTokenIf(Token::PUNCTUATION, ')')) {
            while (!$this->stream->current()->test(Token::PUNCTUATION, ')')) {
                if ($first) {
                    $first = false;
                } else {
                    $this->stream->step(-1);
                    $this->stream->expect(Token::PUNCTUATION, ',');
                }
                $arguments[] = $this->parseExpression(true);
            }
        }
        return $arguments;
    }

    public function parsePostfixExpression()
    {
        $operand = array_pop($this->operand_stack);

        if ($this->stream->nextTokenIf(Token::PUNCTUATION, '(')) {
            //fn call
            $arguments = $this->parseArgumentList();
            $node      = new FunctionNode($operand);
            $node->addArguments($arguments);
        } elseif ($this->stream->nextTokenIf(Token::PUNCTUATION, '[')) {
            //array indexing
            $index = $this->parseExpression(true);
            $node  = new ArrayIndexNode($operand, $index);
        } elseif ($this->stream->nextTokenIf(Token::OPERATOR, $this->unary_postfix_test)) {
            $token    = $this->stream->current();
            $operator = $this->unary_postfix_operators->getOperator($token->getValue());
            $node     = new OperatorNode($operator);
            $node->addOperand(OperatorNode::OPERAND_RIGHT, $operand);
        } else {
            $node = $operand;
        }
        $this->operand_stack[] = $node;
    }

    public function parseDataToken(Token $token)
    {
        switch ($token->getType()) {
            case Token::STRING:
            case Token::LITERAL:
                $this->operand_stack[] = new DataNode($token->getValue());
                break;
            case Token::IDENTIFIER:
                //identifier - handle function calls and array indexing
                $this->operand_stack[] = new IdentifierNode($token->getValue());
                $this->parsePostfixExpression();
                break;
        }
    }

    public function parseArray()
    {
        //iterate over tokens
        $next = $this->stream->next();
        $node = new ArrayNode();
        while (!$next->test(Token::PUNCTUATION, ']')) {
            //expressions are allowed as both array keys and values.
            if ($next->test(Token::PUNCTUATION, '(')) {
                $this->parseExpression();
            } elseif ($next->isDataType()) {
                $this->parseDataToken($next);
            }

            $next = $this->stream->next();
            if ($next->test(Token::PUNCTUATION, ':')) {
                //the previous value was a key
                $key  = array_pop($this->operand_stack);
                $this->parseExpression();
                $next = $this->stream->current();
            } else {
                $key = null;
            }
            $value = array_pop($this->operand_stack);
            $node->add($value, $key);

            if ($next->test(Token::PUNCTUATION, ',')) {
                $next = $this->stream->next();
            } elseif (!$next->test(Token::PUNCTUATION, ']')) {
                $string  = 'Unexpected %s (%s) token found in line %s';
                $message = sprintf($string, $next->getTypeString(), $next->getValue(), $next->getLine());
                throw new SyntaxException($message);
            }
        }
        //push array node to operand stack
        $this->operand_stack[] = $node;
    }

    public function parseToken()
    {
        $token = $this->stream->current();
        if ($token->isDataType()) {
            $this->parseDataToken($token);
        } elseif ($token->test(Token::PUNCTUATION, '(')) {
            $this->parseExpression();
        } elseif ($token->test(Token::PUNCTUATION, '[')) {
            $this->parseArray();
        } elseif ($token->test(Token::OPERATOR, $this->unary_prefix_test)) {
            $this->pushUnaryPrefixOperator($token);
            $this->stream->next();
            $this->parseToken();
        } else {
            $string    = 'Unexpected %s (%s) token found in line %d';
            $exception = sprintf($string, $token->getTypeString(), $token->getValue(), $token->getLine());
            throw new SyntaxException($exception);
        }
    }

    public function parseExpression($return = false)
    {
        //push sentinel
        $this->operator_stack[] = null;

        $this->stream->next();

        $this->parseToken();
        $next = $this->stream->next();
        while ($next->test(Token::OPERATOR, $this->binary_test)) {
            //?: can be handled here?
            $this->pushBinaryOperator($next);
            $this->stream->next();
            $this->parseToken();
            $next = $this->stream->next();
        }
        while (end($this->operator_stack) !== null) {
            $this->popOperator();
        }
        //pop sentinel
        array_pop($this->operator_stack);
        $this->parseConditional();
        if ($return) {
            return array_pop($this->operand_stack);
        }
    }

    public function parseConditional()
    {
        if (!$this->stream->current()->test(Token::PUNCTUATION, '?')) {
            return;
        }
        $node = new OperatorNode(new ConditionalOperator());
        $node->addOperand(OperatorNode::OPERAND_LEFT, array_pop($this->operand_stack));
        if ($this->stream->nextTokenIf(Token::PUNCTUATION, ':')) {
            //?: operator
            $expression_three = $this->parseExpression(true);
            $node->addOperand(OperatorNode::OPERAND_RIGHT, $expression_three);
        } else {
            $expression_two   = $this->parseExpression(true);
            $this->stream->expectCurrent(Token::PUNCTUATION, ':');
            $expression_three = $this->parseExpression(true);

            $node->addOperand(2, $expression_two); //middle expression
            $node->addOperand(OperatorNode::OPERAND_RIGHT, $expression_three);
        }
        $this->operand_stack[] = $node;
    }

    public function popOperator()
    {
        $operator = array_pop($this->operator_stack);
        $node     = new OperatorNode($operator);
        if ($this->binary_operators->exists($operator)) {
            $node->addOperand(OperatorNode::OPERAND_RIGHT, array_pop($this->operand_stack));
        }
        $node->addOperand(OperatorNode::OPERAND_LEFT, array_pop($this->operand_stack));
        array_push($this->operand_stack, $node);
    }

    public function pushBinaryOperator(Token $token)
    {
        $operator = $this->binary_operators->getOperator($token->getValue());
        while ($this->compareToStackTop($operator)) {
            $this->popOperator();
        }
        $this->operator_stack[] = $operator;
    }

    public function pushUnaryPrefixOperator(Token $token)
    {
        $operator = $this->unary_prefix_operators->getOperator($token->getValue());
        while ($this->compareToStackTop($operator)) {
            $this->popOperator();
        }
        $this->operator_stack[] = $operator;
    }

    public function compareToStackTop(Operator $operator)
    {
        $top = end($this->operator_stack);
        if ($top === null) {
            return false;
        }
        if ($this->binary_operators->exists($operator) && $operator === $top) {
            if ($operator->isAssociativity(Operator::LEFT)) {
                return true;
            } elseif ($operator->isAssociativity(Operator::RIGHT)) {
                return false;
            } else {
                $symbols = $operator->operators();
                if (is_array($symbols)) {
                    $symbols = implode(', ', $symbols);
                }
                $message = sprintf('Binary operator %s is not associative.', $symbols);
                throw new ParseException($message);
            }
        }
        return $top->getPrecedence() >= $operator->getPrecedence();
    }
}