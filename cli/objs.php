<?php

// performance test
class Token
{
    public $text;
    public $type;

    private $prev;
    private $next;

    public function __construct($text, $type = null)
    {
        $this->text = $text;
        $this->type = $type;
    }

    public function append(Token $other)
    {
        assert(!$other->prev);
        if ($other->next) {
            $last = $other->findItemBefore(null);
        } else {
            $last = $other;
        }

        if ($this->next) {
            $this->next->prev = $last;
        }

        $last->next = $this->next;

        $other->prev = $this;
        $this->next = $other;
    }

    public function prepend(Token $other)
    {
        assert(!$other->next);
        if ($other->prev) {
            $first = $this->findItemAfter(null);
        } else {
            $first = $other;
        }

        if ($this->prev) {
            $this->prev->next = $first;
        }

        $first->prev = $this->prev;

        $other->next = $this;
        $this->prev = $other;
    }
    
    public function remove()
    {
        $this->removeChain(null);
    }

    public function removeChain(Token $end = null)
    {
        if ($end) {
            assert(!!$this->findItemBefore($end));
        } else {
            $end = $this;
        }

        if ($end->next) {
            $end->next->prev = $this->prev;
            $end->next = null;
        }

        if ($this->prev) { 
            $this->prev->next = $end->next;
            $this->prev = null;
        }
    }

    private function findItemBefore(Token $needle = null)
    {
        for ($p = $this; $p->next; $p = $p->next) {
            if ($p->next === $needle) {
                return $p;
            }
        }

        return $needle === null ? $p : null;
    }

    private function findItemAfter(Token $needle = null)
    {
        for ($p = $this; $p->prev; $p = $p->prev) {
            if ($p->prev === $needle) {
                return $p;
            }
        }

        return $needle === null ? $p : null;
    }
}

// Mem test
$t0 = microtime(true);
$count = 100000;

for ($i = 0; $i < $count; $i++) {
    $tokens[] = new Token((string)$i, 1);
}

$t1 = microtime(true);
printf("Took %.3f ms to make %d tokens\n", ($t1 - $t0) * 1000, $count);
printf("Took %.3f Mb of memeory\n", memory_get_usage(true) / 1024 / 1024);

// Free
$tokens = null;
printf("Using %.3f Mb of memeory\n", memory_get_usage(true) / 1024 / 1024);

$t0 = microtime(true);
$count = 100000;

for ($i = 0; $i < $count; $i++) {
    $tokens[] = array((string)$i, 1);
}

$t1 = microtime(true);
printf("Took %.3f ms to make %d tokens\n", ($t1 - $t0) * 1000, $count);
printf("Took %.3f Mb of memeory\n", memory_get_usage(true) / 1024 / 1024);

