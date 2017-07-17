<?php

namespace Tests\TaskChecker;

use TaskChecker\TextScanner\TokenArray;
use TaskChecker\TextScanner\VariableInjectException;
use TaskChecker\TextScanner\VariableInjector;

class VariableInjectTest extends \PHPUnit_Framework_TestCase
{
    protected $injector;

    public function setUp()
    {
        $this->injector = new VariableInjector();
    }

    public function testInjection()
    {
        $code = TokenArray::fromCode('<?php $a = 1; $b = "";');
        $vars = ['a' => 2, 'b' => 'hello'];
        $output = $this->injector->inject($code, $vars, $errors);

        $this->assertEmpty($errors);
        $this->assertEquals('<?php $a = 2; $b = \'hello\';', $output);
    }

    public function testVarNotFound()
    {
        $code = TokenArray::fromCode('<?php hello();');
        $vars = ['a' => 1];
        $output= $this->injector->inject($code, $vars, $errors);

        $this->assertEmpty($output);
        $this->assertCount(1, $errors);
        $this->assertInstanceOf(VariableInjectException::class, $errors[0]);
        $this->assertEquals('a', $errors[0]->getVarName());
    }
    
    public function testDeclNotFound()
    {
        $code = TokenArray::fromCode('<?php $a + $b; hello($a, $b); $a == $b;');
        $vars = ['a' => 1] ;

        $output = $this->injector->inject($code, $vars, $errors);

        $this->assertCount(1, $errors);
    }    

    public function testCanInjectSeveralTimes()
    {
        $code = TokenArray::fromCode('<?php $a = 0; $b = 0;');
        $vars1 = ['a' => 1];
        $vars2 = ['b' => 2];

        $output1 = $this->injector->inject($code, $vars1, $errors);
        $this->assertNotEmpty($output1);

        $this->assertVarHasValue('a', 1, $output1);
        $this->assertVarHasValue('b', 0, $output1);
        $this->assertVarNotHasValue('a', 0, $output1);

        $output2 = $this->injector->inject($code, $vars2, $errors);
        $this->assertNotEmpty($output2);

        $this->assertVarHasValue('a', 0, $output2);
        $this->assertVarHasValue('b', 2, $output2);
        $this->assertVarNotHasValue('b', 0, $output2);
    }

    private function assertVarHasValue($varName, $valueString, $code)
    {
        $varNameRe = preg_quote($varName);
        $valueRe = preg_quote($valueString);
        // 3 backslashes are needed to pass '\$' into regexp
        $regexp = "/\\\${$varNameRe}\s*=\s*({$valueRe})\s*;/";

        $this->assertRegExp($regexp, $code, "Expected code to contain variable '$varName' with value of '$valueString'. Code: $code");
    }

    private function assertVarNotHasValue($varName, $valueString, $code)
    {
        $varNameRe = preg_quote($varName);
        $valueRe = preg_quote($valueString);
        $regexp = "/\${$varNameRe}\s*=\s*({$valueRe})\s*;/";

        $this->assertNotRegExp($regexp, $code, "Expected code to NOT contain variable '$varName' with value of '$valueString'. Code: $code");
    }    
}