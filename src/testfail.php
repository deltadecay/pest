<?php

namespace pest;


class TestFailException extends \Exception 
{ 
    public function __construct($value, $expect, $negate=false)
    {
        parent::__construct("Expected ".($negate?"not ":"").var_export($expect, true)." but got ".var_export($value, true));
    }
}

