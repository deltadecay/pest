<?php 

namespace pest;

require_once(__DIR__."/utils.php");

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
        // Turn off normalizer, we do not want to collapse and trim whitespace
        $options = [
            //"trimWhitespace" => false,
            //"collapseWhitespace" => false,
            "normalizer" => \pest\utils\noNormalizer(),
        ];
        $hasMatch = utils\hasTextMatch($pattern, $this->value, $options);

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
                    $hasMatch = utils\hasTextMatch($error, $e->getMessage());
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
        if(is_array($this->value)) {
            if(!$this->holds(in_array($item, $this->value, true)))
            {
                throw new TestFailException($this->value, $item, $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "array", false);
        }
    }

    public function toHaveCount($number)
    {
        if (is_array($this->value) || $this->value instanceof Countable) {
            $cnt = count($this->value);
            if(!$this->holds($cnt === $number))
            {
                throw new TestFailException("$cnt items", "$number items", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "array", false);
        }
    }


    // MockFn specific matchers

    public function toHaveBeenCalled() 
    {
        if($this->value instanceof MockFn) {

            if(!$this->holds(count($this->value->getCalls()) > 0))
            {
                throw new TestFailException("0 calls", ">0 calls", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "MockFn", false);
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
            throw new TestFailException($this->value, "MockFn", false);
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
            throw new TestFailException($this->value, "MockFn", false);
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
            throw new TestFailException($this->value, "MockFn", false);
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
            throw new TestFailException($this->value, "MockFn", false);
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
                throw new TestFailException($got, $value, $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "MockFn", false);
        }
    }


    // Dom specific matchers

    public function toBeInTheDocument() 
    {
        if(($this->value instanceof \DOMNode) || $this->value == null) {
            if(!$this->holds($this->value != null))
            {
                throw new TestFailException(null, "to be in document", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "DOMNode", false);
        }
    }

    public function toHaveTextContent($pattern) 
    {
        if(($this->value instanceof \DOMNode) || $this->value == null) {
            $text = null;
            if($this->value != null) {
                $text = $this->value->textContent;   
            }
            $hasMatch = utils\hasTextMatch($pattern, $text);
            if(!$this->holds($hasMatch))
            {
                throw new TestFailException(null, $pattern, $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "DOMNode", false);
        }
    }
    
    public function toHaveClass($className) 
    {
        if(($this->value instanceof \DOMElement) || $this->value == null) {
            $classes = [];
            if($this->value != null) {
                //$nodeClasses = $this->value->attributes->getNamedItem("class")->textContent;
                $nodeClassAttr = $this->value->getAttribute("class");
                $classes = explode(" ", $nodeClassAttr); 
            }
            if(!$this->holds(in_array($className, $classes)))
            {
                throw new TestFailException(implode(" ", $classes), "class $className", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "DOMElement", false);
        }
    }

    public function toHaveValue($expected)
    {
        if(($this->value instanceof \DOMNode) || $this->value == null) {
            $nodeValue = null;
            if($this->value != null) {
                $nodeValue = \pest\utils\getElementValue($this->value);
            }
            if(!$this->holds($nodeValue === $expected))
            {
                throw new TestFailException($nodeValue , $expected, $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "DOMNode", false);
        }
    }

    public function toBeChecked()
    {
        if(($this->value instanceof \DOMElement) || $this->value == null) {
            $checked = false;
            if($this->value != null) {
                $checked = \pest\utils\getBoolAttribute($this->value, "checked");
            }
            if(!$this->holds($checked))
            {
                throw new TestFailException($checked , "to be checked", $this->negate);
            }
        } else {
            throw new TestFailException($this->value, "DOMElement", false);
        }
    }

}

