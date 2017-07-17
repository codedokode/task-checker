<?php 

// Проверяем, что что-нибудь выводится
$this->runner->runScript(function ($output) {    

    $output = trim($output);

    $this->assert->isTrue(
        'проверим, что программа выводит какой-нибудь текст',         
        $output !== '',
        'ничего не выведено'
    );
});

