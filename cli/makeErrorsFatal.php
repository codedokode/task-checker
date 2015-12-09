<?php

error_reporting(-1);

// Fail on any error
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting()) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    return false;
});
