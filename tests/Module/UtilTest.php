<?php

namespace Tests\TaskChecker\Module;

use TaskChecker\Module\Util;

class UtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Util
     */
    private $util;

    public function setUp()
    {
        $this->util = new Util();
    }

    public function provideCorrectCsvExample()
    {
        return [
            [
                "
                a,b,c
                0,1,2
                4,5,6
                ",
                [
                    ['a' => 0, 'b' => 1, 'c' => 2],
                    ['a' => 4, 'b' => 5, 'c' => 6]
                ]
            ],
        ];
    }

    public function provideIncorrectCsvExamples()
    {
        return [
            ["
                a,b,c
            "],
            ["
                a,b,c
                1,2
            "],
            ["
                a,b,c 
                1,2,3,4
            "]
        ];
    }
    

    /**
     * @dataProvider provideCorrectCsvExample
     */
    public function testFromCsv($csvString, array $expectedResult)
    {
        $result = $this->util->fromCsv($csvString);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider provideIncorrectCsvExamples
     */
    public function testFromCsvFailsWithIncorrectData($csvString)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->util->fromCsv($csvString);
    }
    
}