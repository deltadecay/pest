<?php

namespace pest\dom;

require_once(__DIR__."/../expectation.php");
require_once(__DIR__."/dom.php");

use \pest\TestFailException;

// Extend the regular Expectation with DOM specific matchers
class DOMExpectation extends \pest\Expectation
{

    public function toBeInTheDocument() 
    {
        if(isDomNode($this->value) || $this->value == null) 
        {
            if(!$this->holds($this->value != null))
            {
                throw new TestFailException(null, "to be in document", $this->negate);
            }
        } 
        else 
        {
            throw new TestFailException($this->value, "DOMNode", false);
        }
    }

    public function toHaveTextContent($pattern) 
    {
        if(isDomNode($this->value) || $this->value == null) 
        {
            $text = null;
            if($this->value != null) 
            {
                $text = $this->value->textContent;   
            }
            $hasMatch = \pest\utils\hasTextMatch($pattern, $text);
            if(!$this->holds($hasMatch))
            {
                throw new TestFailException($text, $pattern, $this->negate);
            }
        } 
        else 
        {
            throw new TestFailException($this->value, "DOMNode", false);
        }
    }
    
    public function toHaveClass($className) 
    {
        if(isDomElement($this->value) || $this->value == null) 
        {
            $classes = [];
            if($this->value != null) 
            {
                //$nodeClasses = $this->value->attributes->getNamedItem("class")->textContent;
                $nodeClassAttr = $this->value->getAttribute("class");
                $classes = explode(" ", $nodeClassAttr); 
            }
            if(!$this->holds(in_array($className, $classes)))
            {
                throw new TestFailException(implode(" ", $classes), "class $className", $this->negate);
            }
        } 
        else 
        {
            throw new TestFailException($this->value, "DOMElement", false);
        }
    }

    public function toHaveValue($expected)
    {
        if(isDomNode($this->value) || $this->value == null) 
        {
            $nodeValue = null;
            if($this->value != null) 
            {
                $nodeValue = getElementValue($this->value);
            }
            if(!$this->holds($nodeValue === $expected))
            {
                throw new TestFailException($nodeValue , $expected, $this->negate);
            }
        } 
        else 
        {
            throw new TestFailException($this->value, "DOMNode", false);
        }
    }

    public function toHaveDisplayValue($expected)
    {
        if(isDomNode($this->value) || $this->value == null) 
        {
            $nodeValue = null;
            if($this->value != null) 
            {
                $nodeValue = getElementValue($this->value, ["displayValue" => true]);
            }
            if(!$this->holds($nodeValue === $expected))
            {
                throw new TestFailException($nodeValue , $expected, $this->negate);
            }
        } 
        else 
        {
            throw new TestFailException($this->value, "DOMNode", false);
        }
    }

    public function toBeChecked()
    {
        if(isDomElement($this->value) || $this->value == null) 
        {
            $checked = false;
            if($this->value != null) 
            {
                $checked = getBoolAttribute($this->value, "checked");
            }
            if(!$this->holds($checked))
            {
                throw new TestFailException($checked , "to be checked", $this->negate);
            }
        } 
        else 
        {
            throw new TestFailException($this->value, "DOMElement", false);
        }
    }

}

