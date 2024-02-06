<?php 

namespace pest;

use \Exception;

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
            // not a regexp 
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
                        // not a regexp 
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


    public function toContain($item) 
    {
        if(!$this->holds(in_array($item, $this->value, true)))
        {
            throw new TestFailException($this->value, $item, $this->negate);
        }
    }

    public function toHaveBeenCalled() 
    {
        if($this->value instanceof MockFn) {

            if(!$this->holds(count($this->value->getCalls()) > 0))
            {
                throw new TestFailException("0 calls", ">0 calls", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "MockFn", $this->negate);
        }
    }

    public function toHaveBeenCalledTimes($numCalls) 
    {
        if($this->value instanceof MockFn) {
            $actualCalls = count($this->value->getCalls());
            if(!$this->holds($actualCalls == $numCalls))
            {
                throw new TestFailException("$actualCalls calls", "$numCalls calls", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "MockFn", $this->negate);
        }
    }

    public function toHaveBeenNthCalledWith($nthCall, ...$params)
    {
        if($this->value instanceof MockFn) {
            $calls = $this->value->getCalls();
            if(!$this->holds($calls[$nthCall] == $params))
            {
                throw new TestFailException($calls[$nthCall], $params, $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "MockFn", $this->negate);
        }
    }


    public function toHaveReturned() 
    {
        if($this->value instanceof MockFn) {

            $result_types = array_column($this->value->getResults(), "type");
            if(!$this->holds(in_array("return", $result_types)))
            {
                throw new TestFailException("0 returns", ">0 returns", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "MockFn", $this->negate);
        }
    }

    public function toHaveReturnedTimes($numTimesReturns) 
    {
        if($this->value instanceof MockFn) {

            $result_types = array_column($this->value->getResults(), "type");
            $num_returns = count(array_filter($result_types, function($type) { return $type == "return"; }));
            if(!$this->holds($num_returns == $numTimesReturns))
            {
                throw new TestFailException("$num_returns returns", "$numTimesReturns returns", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "MockFn", $this->negate);
        }
    }


    public function toHaveNthReturnedWith($nthCall, $value)
    {
        if($this->value instanceof MockFn) {
            $results = $this->value->getResults();

            $nthValue = $results[$nthCall]['value'];
            $nthType = $results[$nthCall]['type'];

            if(!$this->holds($nthValue == $value && $nthType == "return"))
            {
                $got = $nthType." ";
                if ($nthType == "return") {
                    $got .= $nthValue;
                } else if ($nthType == "throw") {
                    $got .= get_class($nthValue)."(".$nthValue->getMessage().")";
                }
                throw new TestFailException($got, "return $value", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "MockFn", $this->negate);
        }
    }

}

function expect($value)
{
    return new Expectation($value);
}

function test($name, callable $callable) 
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



class MockFn 
{
    private $callable = null;
    private $calls = [];
    private $results = [];


    public function __construct() 
    {
        $this->mockReset();
    }

    public function getCalls() 
    {
        return $this->calls;
    }
    public function getResults() 
    {
        return $this->results;
    }


    public function mockClear()
    {
        $this->calls = [];
        $this->results = [];
    }

    public function mockReset()
    {
        $this->mockClear();
        $this->callable = function() { };
    }

    public function mockImplementation(callable $callable) 
    {
        $this->callable = $callable;
    }

    public function __invoke(...$params)
    {
        $currentCallId = count($this->calls);

        $this->calls[$currentCallId] = $params;
        $this->results[$currentCallId] = ["type" => "incomplete"];
        unset($res);
        $result = ["type" => "return", "value" => $res];
        if(is_callable($this->callable)) {
            try {
                $res = call_user_func_array($this->callable, $params);
                $result["value"] = $res;
            } catch(Exception $e) {
                $result = ["type" => "throw", "value" => $e];
            }
        }

        $this->results[$currentCallId] = $result;

        return $res;
    }
}


function mockfn(callable $callable)
{
    $mock = new MockFn();
    $mock->mockImplementation($callable);
    return $mock;
}