<?php

namespace Tests\TaskChecker\Helper;

class TestHelper
{
    public static function getContainer()
    {
        return $GLOBALS['app'];
    }

    public static function getService($name)
    {
        $container = self::getContainer();
        return $container[$name];
    }

    public static function output($format /* , $args */)
    {
        $args = func_get_args();
        array_shift($args);

        vfprintf(STDERR, $format, $args);
    }

    public static function captureOutput(callable $fn)
    {
        ob_start();
        try {
            $fn();
            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
    
}