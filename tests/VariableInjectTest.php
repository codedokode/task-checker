<?php

namespace tests;

use TextScanner\Text;
use TextScanner\VariableInjector;

class VariableInjectTest extends \PHPUnit_Framework_TestCase
{
    protected $injector;

    public function setUp()
    {
        $this->injector = new VariableInjector();
    }

    public function testInjection()
    {
        $code = Text::fromCode('<?php $a = 1; $b = "";');
        $vars = ['a' => 2, 'b' => 'hello'];
        $output = $this->injector->inject($code, $vars, $errors);

        $this->assertEmpty($errors);
        $this->assertEquals('<?php $a = 2; $b = \'hello\';', $output);
    }

    public function testVarNotFound()
    {
        $code = Text::fromCode('<?php hello();');
        $vars = ['a' => 1];
        $output= $this->injector->inject($code, $vars, $errors);

        $this->assertEmpty($output);
        $this->assertCount(1, $errors);
        $this->assertInstanceOf('TextScanner\\VariableInjectException', $errors[0]);
        $this->assertEquals('a', $errors[0]->getVarName());
    }
    
    public function testDeclNotFound()
    {
        $code = Text::fromCode('<?php $a + $b; hello($a, $b); $a == $b;');
        $vars = ['a' => 1] ;

        $output = $this->injector->inject($code, $vars, $errors);

        $this->assertCount(1, $errors);
    }    
}