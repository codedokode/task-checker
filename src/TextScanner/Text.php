<?php

namespace TextScanner;

class Text implements \Countable
{
    private $tokens;
    private $offsetList = array();
    private $offsetStep = 5;

    public static function fromCode($code)
    {
        $self = new static();
        $self->tokens = token_get_all($code);
        return $self;
    }

    public static function fromTokens(array $tokens)
    {
        foreach ($tokens as $token) {
            if (is_array($token)) {
                assert(count($token) == 3);
                assert(isset($token[0]));
                assert(isset($token[1]));
                assert(isset($token[2]));

                assert(is_string($token[1]));
            } else {
                assert(is_string($token));
            }
        }

        $self = new static();
        $self->tokens = $tokens;

        return $self;
    }
    

    public function count()
    {
        return count($this->tokens);
    }

    public function isEnd($index)
    {
        return $index >= count($this->tokens);
    }

    public function getTokenAt($index)
    {
        if ($index < 0 || $index > $this->count()) {
            throw new Exception("Position #{$index} is outside of range ({$this->count()} total)");            
        }

        return $this->tokens[$index];
    }

    public function getTokenStringAt($index)
    {
        $token = $this->getTokenAt($index);
        return is_string($token) ? $token : $token[1];
    }

    public function getTokenIdAt($index)
    {
        $token = $this->getTokenAt($index);
        return is_string($token) ? $token : $token[0];
    }

    public function getByteOffset($index)
    {
        $row = floor($index / $this->offsetStep);
        $remainder = $index % $this->offsetStep;

        $offset = $this->getOffsetForRow($row);
        for ($pos = $row * $this->offsetStep; $pos < $index; $pos++) {
            $token = $tokens[$pos];
            $string = is_string($token) ? $token : $token[1];
            $offset += strlen($token);
        }

        return $offset;
    }

    public function getByteLength($index)
    {
        return strlen($this->getTokenStringAt($index));
    }

    public function scan()
    {
        return new Scanner($this, 0);
    }

    public function getLineAndColumnAt($index)
    {
        $line = 1;
        $col = 1;
        $newlineFound = false;

        for ($i = $index - 1; $i >= 0; $i--) {            
            $str = $this->getTokenStringAt($i);
            $str = str_replace("\r", '', $str);
            $newlineCount = substr_count($str, "\n");

            if (!$newlineCount) {
                if (!$newlineFound) {
                    $col += mb_strlen($str);
                }
                continue;
            }

            if (!$newlineFound) {
                $trailing = strrpos($str, "\n");
                assert($trailing !== false);
                $trailingWithoutNewline = substr($trailing, 1);

                $col += mb_strlen($trailingWithoutNewline);
            }

            $line += $newlineCount;
            $newlineFound = true;
        }

        $currentStr = $this->getTokenStringAt($index);
        $currentStr = str_replace("\r", '', $currentStr);
        $newlineCount = substr_count($currentStr, "\n");

        if (!$newlineCount) {
            $endLine = $line;
            $endCol = $col + mb_strlen($currentStr) - 1;
        } else {
            $endLine = $line + $newlineCount;

            $trailing = strrpos($currentStr, "\n");
            assert($trailing !== false);
            $trailingWithoutNewline = substr($trailing, 1);

            $endCol = mb_strlen($trailingWithoutNewline);
        }

        return array(
            'line'      =>  $line,
            'col'       =>  $col,
            'endLine'   =>  $endLine,
            'endCol'    =>  $endCol
        );
    }
    
    
    public function generateOutput(array $replacements)
    {   
        foreach ($replacements as $replacement) {
            assert(isset($replacement[0]));
            assert(isset($replacement[1]));
            assert($replacement[0] instanceof Range);
            assert(is_string($replacement[1]));
        }

        // 1) sort ranges
        usort($replacements, function ($a, $b) {
            return $a[0]->getStartBefore() - $b[0]->getStartBefore();
        });

        // 2) check none intersect
        $prev = null;
        $count = count($this);

        foreach ($replacements as $r) {
            $range = $r[0];

            if ($range->getStartBefore() < 0 || $range->getEndBefore() > $count) {
                throw new TextException(
                    "Range {$range->getDescription()} is out of bounds (count of tokens is $count)");
            }

            if ($prev && $prev->doesIntersect($range)) {
                throw new TextException(
                    "Ranges {$range->getDescription()} and {$prev->getDescription()} do intersect");
            }

            $prev = $range;
        }

        // 3) generate output text  
        $output = '';
        $from = 0;

        foreach ($replacements as $r) {
            $range = $r[0];

            $output .= $this->getTextBetween($from, $range->getStartBefore());
            $output .= $r[1];

            $from = $range->getEndBefore();
        }

        $output .= $this->getTextBetween($from, count($this));

        return $output;
    }

    private function getTextBetween($startFrom, $endBefore)
    {
        $output = '';

        for ($i = $startFrom; $i < $endBefore; $i++) {
            $output .= $this->getTokenStringAt($i);
        }

        return $output;
    }

    private function getOffsetForRow($row)
    {
        if ($row >= count($this->offsetList)) {
            assert($row * $this->offsetStep < $this->count());
            $this->createOffsetList(count($this->offsetList), $row);
        }

        return $this->offsetList[$row];
    }

    private function createOffsetList($fromRow, $tillRow)
    {
        $startPos = $fromRow * $this->offsetStep;
        $endPos = min($this->count() - 1, $tillRow * $this->offsetStep);
        $tokens = $this->tokens;

        $offset = $fromRow > 0 ? $this->offsetList[$fromRow] : 0;

        for ($pos = $startPos; $pos <= $endPos; $pos++) {
            
            if ($pos % $this->offsetStep == 0) {
                $row = $pos / $this->offsetStep;
                $this->offsetList[$row] = $offset;
            }

            $token = $tokens[$pos];
            $string = is_string($token) ? $token : $token[1];
            $offset += strlen($string);
        }
    }    

    public static function convertTokenId($id)
    {
        if (is_string($id)) {
            $ids = self::getTokenIds();
            if (isset($ids[$id])) {
                return $ids[$id];
            }

            if (strlen($id) > 1) {
                throw new \Exception("Invalid token id: $id");
            }

            return $id;
        }

        return $id;
    }

    private static function getTokenIds()
    {
        return array(
            "abstract"      => T_ABSTRACT,
            "&="            => T_AND_EQUAL,
            "array"         => T_ARRAY,
            "(array)"       => T_ARRAY_CAST,
            "as"            => T_AS,
            "&&"            => T_BOOLEAN_AND,
            "||"            => T_BOOLEAN_OR,
            "(bool)"        => T_BOOL_CAST,
            "(boolean)"     => T_BOOL_CAST,
            "break"         => T_BREAK,
            "callable"      => T_CALLABLE,
            "case"          => T_CASE,
            "catch"         => T_CATCH,
            "class"         => T_CLASS,
            "__CLASS__"     => T_CLASS_C,
            "clone"         => T_CLONE,
            "?>"            => T_CLOSE_TAG,
            "%>"            => T_CLOSE_TAG,
            ".="            => T_CONCAT_EQUAL,
            "const"         => T_CONST,
            "continue"      => T_CONTINUE,
            // '{$'            => T_CURLY_OPEN,
            "--"            => T_DEC,
            "declare"       => T_DECLARE,
            "default"       => T_DEFAULT,
            "__DIR__"       => T_DIR,
            "/="            => T_DIV_EQUAL,
            "do"            => T_DO,
            '${'            => T_DOLLAR_OPEN_CURLY_BRACES,
            "=>"            => T_DOUBLE_ARROW,
            "(real)"        => T_DOUBLE_CAST,
            "(double)"      => T_DOUBLE_CAST,
            "(float)"       => T_DOUBLE_CAST,
            "::"            => T_DOUBLE_COLON,
            "echo"          => T_ECHO,
            "else"          => T_ELSE,
            "elseif"        => T_ELSEIF,
            "empty"         => T_EMPTY,
            "enddeclare"    => T_ENDDECLARE,
            "endfor"        => T_ENDFOR,
            "endforeach"    => T_ENDFOREACH,
            "endif"         => T_ENDIF,
            "endswitch"     => T_ENDSWITCH,
            "endwhile"      => T_ENDWHILE,
            "eval"          => T_EVAL,
            "exit"          => T_EXIT,
            "die"           => T_EXIT,
            "extends"       => T_EXTENDS,
            "__FILE__"      => T_FILE,
            "final"         => T_FINAL,
            // "finally"       => T_FINALLY,
            "for"           => T_FOR,
            "foreach"       => T_FOREACH,
            "function"      => T_FUNCTION,
            "__FUNCTION__"  => T_FUNC_C,
            "global"        => T_GLOBAL,
            "goto"          => T_GOTO,
            //"__halt_compiler" => T_HALT_COMPILER,
            "if"            => T_IF,
            "implements"    => T_IMPLEMENTS,
            "++"            => T_INC,
            "include"       => T_INCLUDE,
            "include_once"  => T_INCLUDE_ONCE,
            "instanceof"    => T_INSTANCEOF,
            "insteadof"     => T_INSTEADOF,
            "(int)"         => T_INT_CAST,
            "(integer)"     => T_INT_CAST,
            "interface"     => T_INTERFACE,
            "isset"         => T_ISSET,
            "=="            => T_IS_EQUAL,
            ">="            => T_IS_GREATER_OR_EQUAL,
            "==="           => T_IS_IDENTICAL,
            "!="            => T_IS_NOT_EQUAL,
            "<>"            => T_IS_NOT_EQUAL,
            "!=="           => T_IS_NOT_IDENTICAL,
            "<="            => T_IS_SMALLER_OR_EQUAL,
            "__LINE__"      => T_LINE,
            "list"          => T_LIST,
            "and"           => T_LOGICAL_AND,
            "or"            => T_LOGICAL_OR,
            "xor"           => T_LOGICAL_XOR,
            "__METHOD__"    => T_METHOD_C,
            "-="            => T_MINUS_EQUAL,
            "%="            => T_MOD_EQUAL,
            "*="            => T_MUL_EQUAL,
            "namespace"     => T_NAMESPACE,
            "__NAMESPACE__" => T_NS_C,
            "\\"            => T_NS_SEPARATOR,
            "new"           => T_NEW,
            "(object)"      => T_OBJECT_CAST,
            "->"            => T_OBJECT_OPERATOR,
            // "old_function"  => T_OLD_FUNCTION,
            "<?php"         => T_OPEN_TAG,
            // "<?" => T_OPEN_TAG,
            // "<%" => T_OPEN_TAG,
            "<?="           => T_OPEN_TAG_WITH_ECHO,
            "<%="           => T_OPEN_TAG_WITH_ECHO,
            "|="            => T_OR_EQUAL,
            "::"            => T_PAAMAYIM_NEKUDOTAYIM,
            "+="            => T_PLUS_EQUAL,
            "print"         => T_PRINT,
            "private"       => T_PRIVATE,
            "public"        => T_PUBLIC,
            "protected"     => T_PROTECTED,
            "require"       => T_REQUIRE,
            "require_once"  => T_REQUIRE_ONCE,
            "return"        => T_RETURN,
            "<<"            => T_SL,
            "<<="           => T_SL_EQUAL,
            ">>"            => T_SR,
            ">>="           => T_SR_EQUAL,
            "<<<"           => T_START_HEREDOC,
            "static"        => T_STATIC,
            // "parent, true и т.п." => T_STRING,
            "(string)"      => T_STRING_CAST,
            "switch"        => T_SWITCH,
            "throw"         => T_THROW,
            "trait"         => T_TRAIT,
            "__TRAIT__"     => T_TRAIT_C,
            "try"           => T_TRY,
            "unset"         => T_UNSET,
            "(unset)"       => T_UNSET_CAST,
            "use"           => T_USE,
            "var"           => T_VAR,
            "while"         => T_WHILE,
            "^="            => T_XOR_EQUAL,
            // "yield"         => T_YIELD
        );
    }
}
