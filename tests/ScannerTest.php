<?php

namespace tests;

use TextScanner\Range;
use TextScanner\TokenArray;

class ScannerTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToken()
    {
        $this->assertEquals(T_IF, TokenArray::convertTokenId('if'));
        $this->assertEquals(T_IF, TokenArray::convertTokenId(T_IF));
    }

    public function testNext()
    {
        $code = TokenArray::fromCode('<?php /* comment */ echo $x;// end of line comment');
        $scan = $code->scan();

        $this->assertEquals($scan->getTokenId(), T_OPEN_TAG);

        $scan->next();
        $this->assertEquals($scan->getTokenString(), 'echo');

        $scan->next();
        $this->assertEquals($scan->getTokenString(), '$x');

        $scan->next();
        $this->assertEquals($scan->getTokenString(), ';');

        $scan->next();
        $this->assertTrue($scan->isEnd());
    }

    public function testMatchExpression()
    {
        $expressions = array(
            '<?php echo $a + ($b + $c[$d]); echo 1 + 2;'    =>  '$a + ($b + $c[$d])'
        );

        foreach ($expressions as $code => $answer) {

            $code = TokenArray::fromCode($code);
            $scan = $code->scan()->next()->next();

            $this->assertEquals($scan->getTokenString(), '$a');

            $match = $scan->matchExpression();
            $this->assertNotEmpty($match);
            $this->assertEquals($match->getString(), $answer);
        }
    }

    public function testMatchExpressionInBrackets()
    {
        $code = TokenArray::fromCode('<?php echo ($a + ($b * 2)) + $e; echo 1 + 2;');
        $scan = $code->scan()->next()->next()->next();

        $this->assertEquals($scan->getTokenString(), '$a');

        $match = $scan->matchExpression();
        $this->assertNotEmpty($match);
        $this->assertEquals($match->getString(), '$a + ($b * 2)');
    }

    public function testMatchExpressionWithString()
    {
        $code = TokenArray::fromCode('<?php echo $a . "test=${b[$c + ($d * 2)]} test={$b[$c + $d]}"; echo 1 + 2;');
        $scan = $code->scan()->next()->next();

        $this->assertEquals($scan->getTokenString(), '$a');

        $match = $scan->matchExpression();
        $this->assertNotEmpty($match);
        $this->assertEquals($match->getString(), '$a . "test=${b[$c + ($d * 2)]} test={$b[$c + $d]}"');
    }    

    public function testFind()
    {
        $code = TokenArray::fromCode('<?php if ($a > $b); if ($c < $d);');
        $scan = $code->scan();

        $first = $scan->findToken(T_IF);
        $this->assertTrue($first);

        $scan->next();
        $scan->next();

        $this->assertEquals('$a', $scan->getTokenString());

        $second = $scan->findToken(T_IF);
        $this->assertTrue($second);

        $scan->next();
        $scan->next();

        $this->assertEquals('$c', $scan->getTokenString());

        $this->assertFalse($scan->findToken(T_IF));
    }

    public function testForContents()
    {
        $text = TokenArray::fromCode('<?php for ($a = 1; $b < ($c + $d); $e = 1) { "ok"; }');
        $scan = $text->scan()->next()->next()->next();
        
        $match = $scan->matchForContents();
        $this->assertNotEmpty($match);
        
        $this->assertEquals('$a = 1; $b < ($c + $d); $e = 1', $match->getString());

        $scan->next();
        $this->assertEquals('{', $scan->getTokenString());
    }

    public function testFindBadIfs()
    {
        $text = TokenArray::fromCode(
            '<?php echo 1; if ($a < $b) { "ok"; } else echo 1; if ($c < $d); if ($e < $f) echo "No";if ($g < $h) { "ok"; }');
        
        $checker = new \Checker\CurlyBrackets;
        $errors = $checker->check($text);
        $errorTexts = array_map(function ($e) { return $e->getRange()->getString(); }, $errors);
        $expected = array(
            'else echo',
            'if ($c < $d);',
            'if ($e < $f) echo',
        );

        $this->assertEqualValues($expected, $errorTexts);
    }

    private function assertEqualValues(array $a, array $b)
    {
        sort($a);
        sort($b);

        $this->assertEquals($a, $b);
    }

    public function testOutputGeneration()
    {
        $text = TokenArray::fromTokens(array(
            '<?php', "\n",                  // 0, 1
            'a', '=', '1', ';', "\n",      // 2 .. 6
            'b', '=', '2', ';', "\n",      // 7 .. 11
            'c', '=', '3', ';'             // 12 .. 15
        ));

        $range1 = Range::createIncluding($text, 2, 4);
        $range2 = Range::createIncluding($text, 9, 9);

        $replace = [
            [$range1, 'd=0'],
            [$range2, '9']
        ];

        $output = $text->generateOutput($replace);

        $expected = "<?php\nd=0;\nb=9;\nc=3;";

        $this->assertEquals($expected, $output);
    }
    
    public function testCannotGenerateOutputWithIntersectedRanges()
    {
        $text = TokenArray::fromTokens(array('<?php', "\n", 'a', '=', '1', ';'));
        $range1 = Range::createIncluding($text, 1, 4);
        $range2 = Range::createIncluding($text, 4, 5);

        $replace = [
            [$range1, 'x'],
            [$range2, 'y']
        ];

        $this->setExpectedException('TextScanner\\TextException');
        $text->generateOutput($replace);
    }

    public function testCannotGenerateOutputWithInvalidRange()
    {
        $this->setExpectedException('TextScanner\\TextException');

        $text = TokenArray::fromTokens(array('<?php'));
        $range1 = Range::createIncluding($text, 0, 100);

        $replace = [ [$range1, 'a'] ];

        $text->generateOutput($replace);
    }

    public function testCountLineAndColumn()
    {
        $text = TokenArray::fromTokens(array(
            '<?', '$a', '=', '1', "\n",  // 0 .. 4
            '$b', '=', '2', ';', "\r\n\r\n\r\n", // 5 ..9
            '$c', '=', '3'    // 10 .. 12
        ));

        $range1 = Range::createIncluding($text, 0, 0);
        $pos1 = $range1->getLineAndColumn();

        $this->assertEquals(1, $pos1['line']);
        $this->assertEquals(1, $pos1['col']);

        $this->assertEquals(1, $pos1['endLine']);
        $this->assertEquals(2, $pos1['endCol']);

        $pos2 = Range::createIncluding($text, 5, 8)->getLineAndColumn();
        
        $this->assertEquals(2, $pos2['line']);
        $this->assertEquals(1, $pos2['col']);

        $this->assertEquals(2, $pos2['endLine']);
        $this->assertEquals(5, $pos2['endCol']);

        $pos3 = Range::createIncluding($text, 5, 12)->getLineAndColumn();

        $this->assertEquals(2, $pos3['line']);
        $this->assertEquals(1, $pos3['col']);

        $this->assertEquals(5, $pos3['endLine']);
        $this->assertEquals(4, $pos3['endCol']);
    }

    public function testMatchValue()
    {
        $numberText = TokenArray::fromCode('<?php 2,1.5,"hello $world";');

        $scanner = $numberText->scan();
        $scanner->next();
        $number1 = $scanner->matchValue();

        $this->assertNotEmpty($number1);
        $this->assertEquals('2', $number1->getString());
        $this->assertEquals(',', $scanner->getTokenString());

        $scanner->next();
        $number2 = $scanner->matchValue();

        $this->assertEquals('1.5', $number2->getString());
        $this->assertEquals(',', $scanner->getTokenString());

        $scanner->next();
        $string1 = $scanner->matchValue();

        $this->assertEquals('"hello $world"', $string1->getString());
        $this->assertEquals(';', $scanner->getTokenString());
    }

    public function testMatchValueArray()
    {
        $arrayText = TokenArray::fromCode('<?php array(1, 2, 3);');
        $scanner = $arrayText->scan()->next();

        $arrayRange = $scanner->matchValue();

        $this->assertEquals(';', $scanner->getTokenString());
        $this->assertEquals('array(1, 2, 3)', $arrayRange->getString());
    }

    public function testMatchValueHeredoc()
    {
        $heredocText = TokenArray::fromCode("<?php <<<E\na \$b\nE\n;");
        $scanner = $heredocText->scan()->next();

        $heredoc = $scanner->matchValue();

        $this->assertNotEmpty($heredoc);
        $this->assertEquals("<<<E\na \$b\nE\n", $heredoc->getString());
        $this->assertEquals(';', $scanner->getTokenString());
    }

    public function testReadToken()
    {
        $code = TokenArray::fromCode('<?php 1 - 2');
        $scanner = $code->scan()->next();

        $result = $scanner->readTokenString('1');
        $this->assertEquals(true, $result);
        $this->assertEquals('-', $scanner->getTokenString());

        $resultFalse = $scanner->readTokenString('9');
        $this->assertEquals(false, $resultFalse);
        $this->assertEquals('-', $scanner->getTokenString());
    }

    public function testReadTokenId()
    {
        $code = TokenArray::fromCode('<?php $a - $b');
        $scanner = $code->scan()->next();

        $result = $scanner->readTokenId(T_VARIABLE);
        $this->assertEquals(true, $result);
        $this->assertEquals('-', $scanner->getTokenString());

        $resultFalse = $scanner->readTokenId(T_VARIABLE);
        $this->assertEquals(false, $resultFalse);
        $this->assertEquals('-', $scanner->getTokenString());

        $resultTrue = $scanner->readTokenId('-');
        $this->assertEquals(true, $resultTrue);
    }
}

