<?php 

class TestFailException extends Exception 
{ 
    public function __construct($value, $expect, $negate=false)
    {
        parent::__construct("Expected ".($negate?"not ":"").var_export($expect, true)." but got ".var_export($value, true));
    }
}

class Expectation
{
    private $negate = false;
    private $value;

    public function __construct($value) 
    {
        $this->value = $value;
    }

    public function not()
    {
        $this->negate = !$this->negate;
        return $this;
    }

    private function holds($boolexpr) 
    {
        return $this->negate ? !$boolexpr : $boolexpr;
    }

    public function toBe($expected)
    {
        if(!$this->holds($this->value == $expected))
        {
            throw new TestFailException($this->value, $expected, $this->negate);
        }
    }

    public function toBeEqual($expected)
    {
        if(!$this->holds($this->value === $expected))
        {
            throw new TestFailException($this->value, $expected, $this->negate);
        }
    }

    public function toBeCloseTo($expected, $numDigits=2)
    {
        $epsilon = pow(10, -$numDigits) / 2;
        if(!$this->holds(abs($this->value - $expected) < $epsilon))
        {
            throw new TestFailException($this->value, $expected, $this->negate);
        }
    }

    public function toBeGreaterThan($expected)
    {
        if(!$this->holds($this->value > $expected))
        {
            throw new TestFailException($this->value, $expected, $this->negate);
        }
    }
    
    public function toBeGreaterThanOrEqual($expected)
    {
        if(!$this->holds($this->value >= $expected))
        {
            throw new TestFailException($this->value, $expected, $this->negate);
        }
    }
    public function toBeLessThan($expected)
    {
        if(!$this->holds($this->value < $expected))
        {
            throw new TestFailException($this->value, $expected, $this->negate);
        }
    }
    
    public function toBeLessThanOrEqual($expected)
    {
        if(!$this->holds($this->value <= $expected))
        {
            throw new TestFailException($this->value, $expected, $this->negate);
        }
    }

    public function toBeNull()
    {
        if(!$this->holds($this->value === null))
        {
            throw new TestFailException($this->value, null, $this->negate);
        }
    }

    public function toBeSet()
    {
        if(!$this->holds(isset($this->value)))
        {
            throw new TestFailException($this->value, 'set', $this->negate);
        }
    }
    public function toBeUnset()
    {
        if(!$this->holds(!isset($this->value)))
        {
            throw new TestFailException($this->value, 'unset', $this->negate);
        }
    }
    public function toBeTruthy()
    {
        if(!$this->holds($this->value))
        {
            throw new TestFailException($this->value, true, $this->negate);
        }
    }
    public function toBeFalsy()
    {
        if(!$this->holds(!$this->value))
        {
            throw new TestFailException($this->value, false, $this->negate);
        }
    }

    public function toBeInstanceOf($class)
    {
        if(!$this->holds($this->value instanceof $class))
        {
            throw new TestFailException(get_class($this->value), $class, $this->negate);
        }
    }

    public function toMatch($pattern)
    {
        $hasMatch = false;
        // Check if pattern is a regexp
        if (@preg_match($pattern, '') === false){
            //not a regexp 
            $hasMatch = $this->value == $pattern;
        } else {
            // a valid regexp
            $hasMatch = preg_match($pattern, $this->value);
        }
        if(!$this->holds($hasMatch))
        {
            throw new TestFailException($this->value, $pattern, $this->negate);
        }
    }

    public function toThrow($error = null)
    {
        $fun = $this->value;
        if (!is_callable($fun)) {
            throw new Exception("The value to expect(...) should be a function which calls your function");
        }
        $hasMatch = false;

        $thrownExceptionMsg = 'nothing thrown';
        $expectedMsg = 'throw';
        try { 
            $fun();
        } catch (Exception $e) {
            $thrownExceptionMsg = get_class($e)."(".$e->getMessage().")";
            if (isset($error)) {
                if(is_string($error)) {
                    $expectedMsg = $error;
                    // if error is a string we check if it is a pattern that matches the error message
                    if (@preg_match($error, '') === false){
                        //not a regexp 
                        $hasMatch = $e->getMessage() == $error;
                    } else {
                        // a valid regexp
                        $hasMatch = preg_match($error, $e->getMessage());
                    }
                } else {
                    if($error instanceof Exception) {
                        $expectedMsg = get_class($error)."(".$error->getMessage().")";
                        $hasMatch = $e->getMessage() == $error->getMessage() &&
                            get_class($e) == get_class($error);
                    } else {
                        $expectedMsg = $error;
                    }
                }
            } else {
                // if no expected error provided and it throws then ok
                $hasMatch = true;
            }

        }

        if(!$this->holds($hasMatch))
        {
            throw new TestFailException($thrownExceptionMsg, $expectedMsg, $this->negate);
        }
    }
}

function expect($value)
{
    return new Expectation($value);
}

function test($name, $callable) 
{
    try {
        if(is_callable($callable)) {
            $callable();
        } else {
            throw new Exception("test(): callable is not a function");
        }
        echo "\033[92m o PASS \033[0m";
        echo $name, PHP_EOL;
    } catch (Exception $e) {
        echo "\033[91m x FAIL \033[0m";
        echo $name, PHP_EOL, "\t", $e->getMessage(), PHP_EOL;
    }
}
