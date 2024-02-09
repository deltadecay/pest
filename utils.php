<?php

namespace pest\utils;

function normalize($text, $options = []) 
{    
    $trim = isset($options['trimWhitespace']) ? $options['trimWhitespace'] : true;
    $collapseWhitespace = isset($options['collapseWhitespace']) ? $options['collapseWhitespace'] : true;

    $t = $text;
    if ($collapseWhitespace) {
        $t = preg_replace("/\s+/", " ", $t);
    }
    if ($trim) {
        $t = trim($t);
    }
    return $t;
}

function noNormalizer() 
{
    return function($str) { return $str; };
}

function getDefaultNormalizer($options = [])
{
    return function($str) use($options) { return \pest\utils\normalize($str, $options); };
}

function hasTextMatch($pattern, $str, $options = []) 
{
    $exact = isset($options['exact']) ? $options['exact'] : true;
    $normalizer = is_callable($options['normalizer']) ? $options['normalizer'] : getDefaultNormalizer($options);

    if ($str == null) {
        return false;
    }

    // Normalize the input string
    $str = $normalizer($str);

    // Check if pattern is a regexp
    if (@preg_match($pattern, '') === false){
        // not a regexp 
        if($exact) {
            $hasMatch = $pattern == $str;
        } else {
            // Case-insensitive substring check
            $hasMatch = stristr($str, $pattern) !== false;
        }
    } else {
        // a valid regexp
        $hasMatch = preg_match($pattern, $str);
    }
    return $hasMatch;
}


function computeAccessibleName(\DOMNode $node) {

    // TODO See https://www.w3.org/TR/accname-1.1/#mapping_additional_nd
    $name = $node->textContent;
    return $name;
}

function getInputValue($input) 
{
    $type = strtolower($input->getAttribute("type"));
    if ($type == "number") {
        // If number, coerce to number by adding zero
        $value = $input->hasAttribute("value") ? ($input->getAttribute("value") + 0) : null;
    } else if ($type == "checkbox") {
        $value = $input->hasAttribute("checked");
    } else {
        $value = $input->getAttribute("value");
    }
    return $value;
}

function getSelectValue($select)
{
    $multiple = $select->hasAttribute("multiple");
    $dom = $select->ownerDocument;    
    $xpath = new \DOMXPath($dom);
    
    $optionNodes = iterator_to_array($xpath->query("//option", $select));

    $selectedOptions = array_filter($optionNodes, function($node) { 
        return $node->hasAttribute("selected"); 
    });

    // array_filter preserves the keys, so remove them
    $selectedOptions = array_values($selectedOptions);

    if ($multiple) {
        $values = array_map(function($node){
            if($node->hasAttribute("value")) {
                return $node->getAttribute("value");
            } 
            // If no value attribute, use the text content of the option
            return $node->textContent;
        }, $selectedOptions);
        return $values;
    }

    if (count($selectedOptions) == 0) {
        return null;
    } 

    $value = $selectedOptions[0]->getAttribute("value");
    return $value;
}

function getElementValue($node)
{
    if(!isset($node)) {
        return null;
    }

    $value = null;
    if($node instanceof \DOMElement) {
        $tagName = strtolower($node->tagName);
        if ($tagName == "input") {
            $value = getInputValue($node);
        } else if ($tagName == "select") {
            $value = getSelectValue($node);
        } else {
            $value = $node->nodeValue;
        }
    } else if ($node instanceof \DOMNode) {
        $value = $node->nodeValue;
    }
    return $value;
}



function getFirstNonEmptyChildNode($node) 
{
    if(!isset($node)) {
        return null;
    }
    if(!isset($node->childNodes)) {
        return null;
    }
    $childNodes = iterator_to_array($node->childNodes);
    $found = null;
    foreach($childNodes as $childNode)
    {
        if($childNode instanceof \DOMText) {
            $text = trim(normalize($childNode->textContent));
            if(strlen($text) > 0) {
                $found = $childNode;
                break;
            }
        } else {
            $found = $childNode;
            break;
        }
    }
    return $childNode;
}