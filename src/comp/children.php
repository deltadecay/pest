<?php

namespace pest\comp;

require_once(__DIR__."/../utils.php");



class Children implements \IteratorAggregate
{
    private $id;
    private $nodeList = [];
    private $vrdom = null;

    public function __construct($id, $nodeList, $vrdom)
    {
        $this->id = $id;
        $this->nodeList = $nodeList;
        $this->vrdom = $vrdom;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getNodeList()
    {
        return $this->nodeList;
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->nodeList);
    }

    private function nodeToString($node)
    {
        /*
        if($node instanceof \DOMText) {
            return $node->nodeValue;
        } elseif($node instanceof \DOMElement) {
            return $node->ownerDocument->saveXML($node);
        }*/
        return $node->ownerDocument->saveHTML($node);
    }

    /*private function getProps($node)
    {
        $props = [];
        foreach ($node->attributes as $attr) { 
            // If attribute value is a encoded ref, decode it and pass it as the value
            $value = $attr->nodeValue;
            $ref = $this->vrdom->decodeRef($value);
            if($ref instanceof Ref) {
                $value = $ref;
            }
            $props[$attr->localName] = $value; 
        } 
        return $props;
    }*/

    public function map(callable $mapper, $applyOnTextNode=false) {
        $str = "";
        $index = 0;
        foreach ($this->nodeList as $node) {
            $props = $this->vrdom->getProps($node);
            $child = $this->nodeToString($node);
            $apply = true;
            if(!$applyOnTextNode && $node instanceof \DOMText) {
                $apply = false;
            } elseif($applyOnTextNode && $node instanceof \DOMText) {
                if(trim($node->textContent) === "") {
                    // never apply on empty text node
                    $apply = false;
                }
            }
            if($apply) {
                $str .= $mapper($child, $node, $props, $index);
            } else {
                $str .= $child;
            }
            $index++;
        }
        return \pest\utils\normalize($str);
    }

    public function __toString()
    {
        
        $str = "";
        foreach ($this->nodeList as $node) {
            $str .= $this->nodeToString($node);
        }
        return \pest\utils\normalize($str);
        
        //return "<children instanceid=\"".$this->id."\" />";
    }
}
