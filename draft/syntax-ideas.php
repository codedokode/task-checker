<?php

// Hello World
$tester = new Tester();
$tester->checkOutputMatch('/\S/', 'The program must print something, ...');

// pu: Hello world
$result = $program->run();
$this->assertNotEmpty($program->getOutput(), 'The program must output ...');

// cc: Hello World
$I->runProgram();
$I->checkOutputIsNotEmpty();

// ...
$I->run();
$I->seeOutputIsNotEmpty();


$helper = $I->loadHelper('exchange');
$I->runProgramWith([
    'dollars'   =>  100,
    'rate'      =>  30
]);

$I->checkEqualsApproximately('', 3000);


$I->loadHelper('exchange');
$I->testProgramWithCsv(<<<EOF 
dollars,    rate,   result, error
    100,    30,     3000,   0.01
    200,    20,     4000,   0.01
EOF;
);

// Кубики
$I->loadHelper('dices');
$I->testProgramWithCsv(<<<EOF
r1, r2, h1, h2, winner
1,  2,  3,  5,  human
1,  1,  2,  2,  draw
2,  5,  1,  1,  robot
EOF;
);

class Helper\Dices 
{
    public function init()
    {
        $this->addInputs('r1', 'r2', 'h1', 'h2');
                
        $this->addResultMatch('winner', 'human', 'победил человек');
        $this->addResultMatch('winner', 'draw', 'ничья');
        $this->addResultMatch('winner', 'draw', 'выпали даблы');
        $this->addResultMatch('winner', 'robot', 'победил робот');
    }
}

// Курс доллара
$tester = new Tester();
$tester->set('exchangeRate', 50);
$tester->inputCsv(<<<EOF 
dollars, result
100, 5000,
200, 10000
EOF
);
$tester->read('/на {result} рублей/ui');


// Альтернативная
$tester = new Tester();
$tester->setRandomInput('exchangeRate', 10, 100);
$tester->setRandomInput('dollars', 10, 10000);
$tester->calculate('result', function ($dollars, $exchangeRate) {
    return $dollars * $exchangeRate;
});
$tester->runTimes(4);
$tester->read('/на {result} рублей/ui');


// pu: Курс
$result = $program->run(array(
    'exchangeRate'  =>  10,
    'dollars'       =>  30
));

$this->assetEquals(300, $result->read('/на {result} рублей/ui'));


// cc: Курс
$I->set('exchangeRate', 10);
$I->set('dollars', 30);
$I->runProgram();
$I->see('/на {result} рублей/ui', 300)


$I->runProgramWith(array(
    'exchangeRate'  =>  30,
    'dollars'       =>  30
));
$I->read('/на {result} рублей/');
$I->assertApprox('result', 300, 1);

// ..
$I->haveTable("
exchangeRate,   dollars,    result
          10,        30,       300
");

$I->runProgram();
$I->checkEachResult(function($I) {
    $I->read('');
});


// ...
$result = $t->run([
    'exchangeRate'  =>  20,
    'dollars'       =>  3
]);

$result->read('на {result} рублей', 60);
// ...


$t->runWithCsv("
exchangeRate,   dollars,    result
         100,         5,       500
          30,         30,       4
");

$t->forEach(function($test) {
    $test->checkEquals('/можно обменять на {result} (р|рубл)/ui', $test->get('result'));
});

// 
$t->runSeveralTimes(4);
$t->forEach(function ($result) {
    $result->read('выпало {number:int}/ui')->
        shouldBeBetween(1, 6);
    // Ошибка: программа вернула результат number=8, но 
    //         он должен быть от 1 до 6
        
});

$t->forAll(function (array $results) {
    ...
});

// ... $result->isOneOf('result', 'человек победил')



// Кубик
$tester->runTimes(4);
$tester->read('/выпало {number}/ui');
$tester->checkInteger('number', 'На кубике должно было выпасть целое число, а выпало {value}');
$tester->checkBetween('number', 1, 6, 'На кубике должно было выпасть число от 1 до 6, а выпало {value}');
$tester->checkAll(function (array $results) {
    $numbers = Array::pluck($results, 'number');
    $diff = array_count_values($numbers);

    if (count($diff) < 1) {
        $errors->add('Программа должна выдавать разные случайные значения, но твоя программа всегда выводит {value}',
            array('value'   =>  $numbers[0]));
    }
});



// Игра в кубики
$tester->readOneOf('winner', 'человек победил', 'компьютер победил', 'ничья', 'победила дружба');
$tester->inputCsv(<<<EOF 
anonDice1, anonDice2, compDice1, compDice2, winner
2, 2, 4, 6, 1
2, 3, 1, 1, 2 
3, 4, 2, 5, 3
1, 1, 6, 6, 4
EOF 
);



// Айфон в кредит
$tester->readLast('всего выплачено {result:number}');
$tester->setDeviation(0.01);
$tester->inputCsv(<<<EOF 
creditBalance, result
40000, 61270
1000, 2030
4000, 6120
EOF 
);

// Средний балл
$tester->input([
    [
        "rates":    [1, 2, 3, 4, 5],
        "result":   3,
    ],
    [
        "rates":    [10, 10, 10, 10, 10],
        "result":   5
    ]
]);
$tester->read("средний балл {number}");

// Средний рост
$tester->set('anonHeight', 140);
$tester->inputOne([
    "Иван"  =>  130,
    "Антон" =>  150
]);
$tester->read('в классе {number} человек выше');

// pu: avg
$rates = [1, 2, 3, 4, 5];
$result = $program->run(['rates' => $rates]);
$this->assertEquals(3, $result->read("средний балл {number}"));

{
    "inputs": {
        "rates": "Массив оценок"
    },
    "outputs": [
        "средний балл {number}"
    ]
}

class AverageRateProblem extends Problem
{
    public function getSpec()
    {
        return [
            'vars'    =>  ['rates' => 'Массив оценок'],
            'outputs' =>  ['Средний балл {number}'],
            'text'    =>   "
                Текст задачи
            "
        ];
    }
    
    public function validate($program)
    {
        $inputs = [
            ['rates' => [1, 2, 3]],
            ['rates' => [4, 5, 6]] 
        ]; 

        $this->runProgram($inputs, function ($input, $result) {

        });
    }    
}



$data = [
    ['rates' => [1, 2, 3], 'expect' => 2],
    ['rates' => [4, 5, 6], 'expect' => 5] 
]; 

$I->setInputs(['rates']);
$I->runProgramWith($data, function ($result, $expect) {
    $number = $I->scanner->read('средний балл {number}');
    $I->checkEquals($number, $expect, 0.1, 'Средний балл должен быть равен {expected}, а он равняется {actual}');
});

