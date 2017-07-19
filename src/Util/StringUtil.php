<?php

namespace TaskChecker\Util;

class StringUtil
{
    public static function stringify($value)
    {
        switch (gettype($value)) {
            case 'double':
            case 'integer':
            case 'boolean':
            case 'string':
            case 'NULL':
                return var_export($value, true);

            case 'resource':
                return "<resource #".intval($value).">";

            case 'array':
                $elements = [];
                $elementLimit = 50;
                $number = 0;

                foreach ($value as $key => $element) {
                    $number++;
                    if ($number > $elementLimit) {
                        $more = count($value) - $elementLimit;
                        $elements[] = "... +{$more} элементов";
                        break;
                    }

                    $elements[] = sprintf("%s => %s", 
                        var_export($key, true), 
                        self::stringify($element));
                }

                $result = sprintf("[%s]", implode(', ', $elements));
                return $result;

            case 'object':
                $className = get_class($value);

                if (method_exists($value, '__toString()')) {
                    $result = sprintf("<object %s: %s>", $className, $value->__toString());
                } else {
                    $result = sprintf("<object %s>", $className);
                }

                return $result;

            default:
                throw new \Exception(sprintf("Failed to stringify type '%s'", gettype($value)));
        }
    }
}