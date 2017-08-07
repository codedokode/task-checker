<?php 

namespace Tests\TaskChecker\ProblemParser;

use TaskChecker\Problem;
use TaskChecker\ProblemParser\ProblemSerializer;

class ProblemSerializerTest extends \PHPUnit_Framework_TestCase
{
    public function testCanDeserializeSerializedItem()
    {
        $p1 = new Problem('id1', 'name1', 'description1');
        $p1->relativeUrl = '/relativeUrl1';
        $p1->codeSample = 'codeSample1';
        $p1->codeSampleUrl = 'http://codeSampleUrl1';
        $p1->hints = ['hint1.1', 'hint1.2'];
        $p1->examples = ['example1.1', 'example1.2'];

        $problems = [$p1];

        $serializer = new ProblemSerializer;
        $jsonString = $serializer->serialize($problems);
        $restoredItems = $serializer->deserialize($jsonString);

        $this->assertCount(1, $restoredItems);
        $r1 = $restoredItems[0];

        $getters = ['getId', 'getName', 'getDescription', 
            'getRelativeUrl', 'getCodeSample', 'getCodeSampleUrl', 
            'getHints', 'getExamples'
        ];

        foreach ($getters as $getter) {
            $expected = call_user_func([$p1, $getter]);
            $actual = call_user_func([$r1, $getter]);

            $this->assertEquals($expected, $actual, "values from $getter must be equal");
        }
    }
}
