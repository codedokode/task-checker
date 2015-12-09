<?php

namespace TextReader;

class Reader
{
    private $regexp;
    private $varTypes;

    public static function create($expression)
    {
        $reader = new self;
        $reader->parseExpression($expression);

        return $reader;
    }
    
    /**
     * Expression syntax:
     *
     * x={x:number}, y={y:string}, z={z}
     */
    private function parseExpression($expression)
    {
        $splitBy = '/\{[^}]*(\}|\\Z)/';
        $parts = preg_split($splitBy, $expression, PREG_SPLIT_NO_EMPTY);

        $structure = [];
        foreach ($parts as $part) {
            if (preg_match('/^\{/', $part)) {
                $structure[] = $this->parseVariableToken($part);
            } else {
                $element = ['text' => $part, ''];
                $structure[]
            }
        }
    }
    
}