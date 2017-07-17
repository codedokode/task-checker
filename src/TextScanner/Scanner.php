<?php

namespace TaskChecker\TextScanner;

class Scanner
{
    private $text;
    private $position;
    private $skipTokens = array(
        T_COMMENT       =>  true,
        T_DOC_COMMENT   =>  true,
        T_WHITESPACE    =>  true //,
        // T_ML_COMMENT    =>  true
    );

    private $literalTokens = array(
        T_CONSTANT_ENCAPSED_STRING  =>  true,
        T_ENCAPSED_AND_WHITESPACE   =>  true,
        T_INLINE_HTML               =>  true,
        T_STRING_VARNAME            =>  true        
    );

    private $openExpression = array(
        '(' =>  true,
        '[' =>  true,
    );

    private $closeExpression = array(
        '}' =>  true,
        ']' =>  true,
        ')' =>  true,
        ';' =>  true,
        T_CLOSE_TAG     =>  true
    );

    public function __construct(TokenArray $text, $position = 0)
    {
        $this->text = $text;
        $this->position = $this->skipForward($position);
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getText()
    {
        return $this->text;
    }

    private function skipForward($position)
    {
        $end = $this->text->count();

        while ($position < $end) {
            $token = $this->text->getTokenIdAt($position);
            if (!isset($this->skipTokens[$token])) {
                break;
            }

            $position ++;
        }

        return $position;
    }

    private function getNextPos($pos)
    {
        return $this->skipForward($pos + 1);
    }

    private function getEnd()
    {
        return $this->text->count();
    }

    public function next()
    {
        if (!$this->isEnd()) {
            $this->position = $this->skipForward($this->position + 1);
        }

        return $this;
    }

    public function isEnd()
    {
        return $this->text->isEnd($this->position);
    }

    public function getTokenId()
    {
        return $this->text->getTokenIdAt($this->position);
    }

    public function getTokenString()
    {
        return $this->text->getTokenStringAt($this->position);
    }

    public function findToken($tokenId)
    {
        $pos = $this->position;
        $tokenId = TokenArray::convertTokenId($tokenId);

        if ($this->findTokenPos($tokenId, $pos)) {
            $this->position = $pos;
            return true;
        }

        return false;
    }

    public function findAny(array $tokens)
    {   
        $tokens = array_map('TextScanner\\TokenArray::convertTokenId', $tokens);
        $pos = $this->position;

        if ($this->findAnyTokenPos($tokens, $pos)) {
            $this->position = $pos;
            return true;
        }

        return false;
    }

    private function findAnyTokenPos(array $tokenIds, &$pos)
    {
        $end = $this->getEnd();
        $tokenHash = array_flip($tokenIds);

        for (; $pos < $end; $pos = $this->getNextPos($pos)) {

            $id = $this->text->getTokenIdAt($pos);

            if (isset($tokenHash[$id]) && 
                    !$this->isLiteral($id)) {
                return true;
            }
        }

        return false;
    }

    private function findTokenPos($tokenId, &$pos)
    {
        return $this->findAnyTokenPos(array($tokenId), $pos);
    }

    private function isLiteral($tokenId)
    {
        return isset($this->literalTokens[$tokenId]);
    }

    private function consumeSequence(array $args, &$position)
    {
        $end = $this->getEnd();

        foreach ($args as $string) {

            $id = $this->text->getTokenIdAt($position);

            if ($position >= $end || $id != $string || $this->isLiteral($id)) {
                return false;
            }

            $position = $this->getNextPos($position);
        }

        return true;
    }

    public function findSequence($arg1)
    {
        $args = func_get_args();
        $args = array_map('TokenArray::convertTokenId', $args);
        $pos = $this->position;

        while ($this->findTokenPos($arg1, $pos)) {
            if ($this->consumeSequence($args, $pos)) {
                $this->position = $pos;
                return true;
            }
        }

        return false;
    }

    public function readTokenId($id)
    {
        $string = TokenArray::convertTokenId($id);
        $pos = $this->position;

        if ($this->consumeToken($pos, $this->getEnd(), $string)) {
            $this->next();
            return true;
        }

        return false;
    }

    public function readTokenString($string)
    {
        $pos = $this->position;

        if ($this->getTokenString() == $string) {
            $this->next();
            return true;
        }

        return false;
    }

    private function consumeToken(&$pos, $end, $id)
    {
        if ($pos >= $end || 
            $this->isLiteral($this->text->getTokenIdAt($pos)) ||
            $this->text->getTokenIdAt($pos) != $id) {

            return false;
        }

        $pos = $this->getNextPos($pos);
        return true;
    }

    private function consumeBlock(&$pos, $end, &$valid)
    {
        assert($this->text->getTokenStringAt($pos) == '{');
        $pos = $this->skipForward($pos);

        while ($pos < $end && $valid) {
            $this->consumeExpression($pos, $valid);

            if (!$valid) {
                break;
            }

            $string = $this->text->getTokenStringAt($pos);
            $pos = $this->skipForward($pos + 1);

            if ($string == ';') {
                continue;
            }

            if ($string == '}') {
                return $pos;                
            }

            $valid = false;
            break;
        }

        $valid = false;
    }

    private function consumeExpression(&$pos, $end, &$valid)
    {
        while ($pos < $end && $valid) {

            $tokenId = $this->text->getTokenIdAt($pos);

            // End expression
            if (isset($this->closeExpression[$tokenId])) {
                return true;
            }

            // Start string
            if ($tokenId == '"' || $tokenId == T_START_HEREDOC) {
                $this->consumeStringAt($pos, $end, $valid);
                continue;
            }

            // Start bracketed expr
            if ($tokenId == '(' || $tokenId == '[') {
                $this->consumeBracketedExpression($pos, $end, $valid);
                continue;
            }

            $pos = $this->getNextPos($pos);
        }

        $valid = false;
    }

    private function consumeStringAt(&$pos, $end, &$valid)
    {
        $startToken = $this->text->getTokenIdAt($pos);
        $pos = $this->getNextPos($pos);

        assert($startToken == T_START_HEREDOC || $startToken = '"');
        $endToken = $startToken == T_START_HEREDOC ? T_END_HEREDOC : '"';

        while ($valid && $pos < $end) {
            $token = $this->text->getTokenIdAt($pos);

            if ($token == T_CURLY_OPEN || $token == T_DOLLAR_OPEN_CURLY_BRACES) {
                $this->consumeInnerExpression($pos, $end, $valid);
                continue;
            }

            $pos = $this->getNextPos($pos);

            if ($token == $endToken) {
                return;
            }
        }

        // Unexpected break
        $valid = false;
    }

    private function consumeInnerExpression(&$pos, $end, &$valid)
    {
        $pos = $this->getNextPos($pos);

        while ($valid && $pos < $end) {

            $this->consumeExpression($pos, $end, $valid);

            if (!$valid) {
                return false;
            }

            $id = $this->text->getTokenIdAt($pos);
            
            if ($id == '}') {
                $pos = $this->getNextPos($pos);
                return;
            }
        }

        $valid = false;
    }

    private function consumeBracketedExpression(&$pos, $end, &$valid)
    {
        $startToken = $this->text->getTokenIdAt($pos);
        assert($startToken == '[' || $startToken == '(');
        $endToken = $startToken == '[' ? ']' : ')';

        $pos = $this->getNextPos($pos);

        $this->consumeExpression($pos, $end, $valid);

        if (!$valid) {
            return;
        }

        $id = $this->text->getTokenIdAt($pos);

        if ($id == $endToken) {
            $pos = $this->getNextPos($pos);
            return;
        }

        $valid = false;
    }

    public function matchExpression()
    {
        $pos = $this->position;
        $valid = true;
        $this->consumeExpression($pos, $this->getEnd(), $valid);

        if ($valid) {
            $start = $this->position;
            $this->position = $pos;

            return Range::createExcludingEnd($this->text, $start, $pos);
        }

        return null;
    }

    public function matchForContents()
    {
        $start = $pos = $this->position;
        $end = $this->getEnd();
        $valid = true;

        for ($i = 0; $i < 3; $i++) {

            $this->consumeExpression($pos, $this->getEnd(), $valid); 

            if (!$valid) {
                return null;
            }

            if ($i < 2) {
                if (!$this->consumeToken($pos, $end, ';')) {
                    return null;
                }
            }
        }

        $end = $pos;
        $this->position = $pos;
        return Range::createExcludingEnd($this->text, $start, $end);
    }

    /**
     * When matches, advaces position and returns a range
     * Otherwise returns null
     */
    public function matchValue()
    {
        $start = $pos = $this->position;
        $end = $this->getEnd();

        $this->consumeValue($pos, $end, $found);
        if (!$found) {
            return null;
        }

        $this->position = $pos;
        return Range::createExcludingEnd($this->text, $start, $pos);
    }
        
    private function consumeValue(&$pos, $end, &$found)
    {        
        /*
         * Value is: 
         *     T_ARRAY ( EXPR )                 array(1, 2, 3)
         *     T_CONSTANT_ENCAPSED_STRING       'hello'
         *     T_DNUMBER                        2.12
         *     " STRING_CONTENT "               "Hello {$world[0]->call()}"
         *     T_LNUMBER                        0x20, 120
         *     T_START_HEREDOC STR_CONTENT T_END_HEREDOC   
         */

        $found = false;
        $firstToken = $this->text->getTokenIdAt($pos);

        switch ($firstToken) {
            case T_CONSTANT_ENCAPSED_STRING:
            case T_DNUMBER:
            case T_LNUMBER:
                $pos = $this->getNextPos($pos);
                $found = true;
                return;

            case T_ARRAY:
                $pos = $this->getNextPos($pos);
                $secondToken = $this->text->getTokenIdAt($pos);

                if ($secondToken != '(') {
                    return;
                }

                $found = true;
                $this->consumeBracketedExpression($pos, $end, $found);
                return;

            case T_START_HEREDOC:
            case '"':
                $found = true;
                $this->consumeStringAt($pos, $end, $found);

                return;

            default:
                // not found
        }
    }

    public function goWhere(callable $predicate)
    {
        for ( ; !$this->isEnd(); $this->next()) {
            if ($predicate($this)) {
                return true;
            }
        }

        return false;
    }
}