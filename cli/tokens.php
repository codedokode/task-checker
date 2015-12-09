<?php

// $code = '<?php echo "{$a[0]}" . function () {} . array(1, 2, 3) . array()';
$code = "<?php echo <<<ND\ntext \$var \nND;\n";
$t = token_get_all($code);

foreach ($t as $token) {
    if (is_string($token)) {
        echo "$token\n";
    } else {
        printf("%s (%s)\n", token_name($token[0]), $token[1]);
    }
}
