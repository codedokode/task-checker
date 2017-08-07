<?php 

namespace Tests\TaskChecker\ProblemParser;

use TaskChecker\ProblemParser\Parser;
use Tests\TaskChecker\Helper\TestHelper;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParserCanParseHtml()
    {
        $path = TestHelper::getAssetsDir() . '/problem-markup-test.html';
        $html = file_get_contents($path);
        $baseUrl = "http://example.com/page1?query";

        $parser = new Parser;
        $problems = $parser->parsePage($html, $baseUrl);

        $this->assertCount(2, $problems);
        list($p1, $p2) = $problems;

        $this->assertEquals('task1', $p1->getId());
        $this->assertContains('ProblemName1', $p1->getName(), '', true);
        $this->assertContains('Description1', $p1->getDescription(), '', true);
        $this->assertContains('code-sample-1', $p1->getCodeSampleUrl(), '', true);

        $this->assertContains('codeSample1.1', $p1->getCodeSample(), '', true);
        $this->assertContains('codeSample1.2', $p1->getCodeSample(), '', true);

        // There must be a newline
        $this->assertRegExp(
            "~codeSample1\.1.*\n.*codeSample1.2~", 
            $p1->getCodeSample()
        );

        $hints = $p1->getHints();
        $this->assertCount(2, $hints);
        $this->assertContains('Hint1.1', $hints[0]);
        $this->assertContains('Hint1.2', $hints[1]);

        $examples = $p1->getExamples();
        $this->assertCount(2, $examples);
        $this->assertContains('Example1.1', $examples[0]);
        $this->assertContains('Example1.2', $examples[1]);

        $this->assertEquals('task2', $p2->getId());
        $this->assertContains('ProblemName2', $p2->getName(), '', true);
        $this->assertContains('Description2', $p2->getDescription(), '', true);
    }
}

