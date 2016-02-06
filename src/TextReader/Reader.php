<?php

namespace TextReader;

use Util\ArrayUtil;

class Reader
{
    private $regexp;
    private $variables;
    private $text;

    public function __construct($expression)
    {
        $this->parseExpression($expression);
    }
    
    /**
     * Expression syntax:
     *
     * x={x}, y={y}, z={z}
     */
    private function parseExpression($expression)
    {
        $splitBy = '/(\{[^}]*(?:\}|\\Z))/';
        $parts = preg_split($splitBy, $expression, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $this->variables = [];

        $structure = [];

        foreach ($parts as $part) {
            if (preg_match('/^\{/', $part)) {
                $structure[] = $this->parseVariableToken($part);
            } else {
                $text = $this->normalizeSpaces($part);
                $regexp = $this->generateRegexpForText($text);

                $element = [
                    'regexp'    => $regexp,
                    'text'      => $text
                ];

                $structure[] = $element;
            }
        }

        $this->text = $this->generateText($structure);
        $this->regexp = $this->generateRegexp($structure);
    }

    private function generateText(array $structure)
    {
        return implode('', ArrayUtil::pluck($structure, 'text'));
    }
    
    private function generateRegexp(array $structure)
    {
        $middle = implode('', ArrayUtil::pluck($structure, 'regexp'));
        $regexp = "/(\b|\A){$middle}(\b|\Z)/ui";

        return $regexp;
    }
    
    private function parseVariableToken($part)
    {
        if (!preg_match("/^\{([a-z_][a-z_0-9]*)\}$/ui", $part, $m)) {
            throw new \Exception("Reader: Invalid variable in expression: '$part'");
        }

        $varName = $m[1];

        if (isset($this->variables[$varName])) {
            throw new \Exception("Reader: Double variable usage in expression: '$part'");
        }

        $this->variables[$varName] = $varName;

        return [
            'regexp' => "(?P<{$varName}>\S+)",
            'text'  =>  '{...}'
        ];
    }    

    private function normalizeSpaces($text)
    {
        return preg_replace("/[^\p{L}\p{N}]+/u", ' ', $text);
    }    

    private function generateRegexpForText($text)
    {
        $parts = preg_split("/(\s)/", $text, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $regexp = '';

        foreach ($parts as $part) {
            if ($part == ' ') {
                $regexp .= "(\A|\Z|[^\p{L}\p{N}]+)";
            } else {
                $regexp .= preg_quote($part);
            }
        }

        return $regexp;
    }
    
    public function matchOne($text, array &$matches)
    {
        $matches = [];

        if (!preg_match($this->regexp, $text, $m)) {
            return false;
        }

        foreach ($this->variables as $varName) {
            $matches[$varName] = $m[$varName];
        }

        return true;
    }

    public function matchAll($text)
    {
        $matches = [];
        preg_match_all($this->regexp, $text, $m, PREG_SET_ORDER);

        foreach ($m as $match) {
            foreach ($this->variables as $varName) {
                $value = $match[$varName];
                if (is_numeric($value)) {
                    $value = floatval($value);
                }
                
                $set[$varName] = $value;
            }

            $matches[] = $set;
        }

        return $matches;
    }
    
    
    public function getVariableNames()
    {
        return array_values($this->variables);
    }
    
    public function getPatternText()
    {
        return $this->text;
    }
    
}