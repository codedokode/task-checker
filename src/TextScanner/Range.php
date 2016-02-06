<?php

namespace TextScanner;

class Range
{
    private $text;
    private $startBefore;
    private $endBefore;

    private function __construct(TokenArray $text, $startBefore, $endBefore)
    {
        $this->text = $text;
        $this->startBefore = $startBefore;
        $this->endBefore = $endBefore;
    }

    public static function createEmpty(TokenArray $text, $before = 0)
    {
        return new self($text, $before, $before);
    }    

    public static function createIncluding(TokenArray $text, $from, $to)
    {
        return new self($text, $from, $to + 1);
    }

    public static function createExcludingEnd(TokenArray $text, $startBefore, $endBefore)
    {
        return new self($text, $startBefore, $endBefore);
    }
    

    public function getString()
    {
        $string = '';

        for ($i = $this->startBefore; $i < $this->endBefore; $i++) {
            $string .= $this->text->getTokenStringAt($i);
        }

        return $string;
    }

    public function getLength()
    {
        return $this->endBefore - $this->startBefore;
    }

    public function getTokenStringAt($pos)
    {
        assert($pos < $this->getLength());
        return $this->text->getTokenStringAt($pos + $this->startBefore);
    }

    public function getStartBefore()
    {
        return $this->startBefore;
    }

    public function getEndBefore()
    {
        return $this->endBefore;
    }
    
    public function doesIntersect(Range $other)
    {
        return $other->endBefore > $this->startBefore &&
            $other->startBefore < $this->endBefore;
    }
    
    public function getDescription()
    {
        return "[{$this->startBefore}..{$this->endBefore})";
    }
    
    public function getLineAndColumn()
    {
        assert($this->getLength() > 0);

        $start = $this->text->getLineAndColumnAt($this->startBefore);
        $end = $this->text->getLineAndColumnAt($this->endBefore - 1);

        return array(
            'line'      =>  $start['line'],
            'col'       =>  $start['col'],
            'endLine'   =>  $end['endLine'],
            'endCol'    =>  $end['endCol']
        );
    }
    
}