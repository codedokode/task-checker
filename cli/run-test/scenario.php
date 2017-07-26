<?php

$csv = "
@dollars,   @exchange,  rubles
20,         30,         600
0,          30,         0
1000,       50,         50000
";

$data = $this->util->fromCsv($csv);

$this->runner->queueRunningForArray($data, function ($output, array $row) {
    $rubles = $this->reader->readOne($output, "можно обменять на {x}");
    $this->assert->isNumber($rubles);
    $this->assert->isEqualApproximately($row['rubles'], $rubles, 0.02);
});
