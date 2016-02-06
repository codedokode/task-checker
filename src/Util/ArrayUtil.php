<?php

namespace Util;

class ArrayUtil
{
    public static function pluck($collection, $key)
    {
        $results = [];
        foreach ($collection as $item) {
            $results[] = $item[$key];
        }

        return $results;
    }
    
}