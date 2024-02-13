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

    if ($str === null) {
        return false;
    }

    // Normalize the input string
    $str = $normalizer($str);

    if($pattern == "") {
        // Special case when pattern is empty
        return $pattern == $str;
    }

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


function getDocument($node)
{
    if($node instanceof \DOMDocument) {
        $doc = $node;
    } else {
        $doc = $node->ownerDocument;    
    }
    return $doc;
}

function computeAccessibleName(\DOMNode $node, $traversal = []) 
{
    // TODO See https://www.w3.org/TR/accname-1.1/#mapping_additional_nd
    $dom = getDocument($node);    
    $xpath = new \DOMXPath($dom);

    $tagName = strtolower($node->tagName);
    $accName = "";

    if ($node instanceof \DOMText) {
        $text = normalize($node->textContent);
        return $text;
    }

    if($node->hasAttribute("aria-labelledby") && $traversal['aria-labelledby']==0) {
        // Note! aria-labelledby can be a space separated list
        $ids = explode(" ", $node->getAttribute("aria-labelledby"));
        $accNames = [];
        foreach($ids as $id) {
            $refNameNodes = $xpath->query("//*[@id='".$id."']");
            $traversal['source'] = $node;
            $traversal['aria-labelledby']++;
            foreach($refNameNodes as $refNameNode) {
                $accNames[] = normalize(computeAccessibleName($refNameNode, $traversal));
            }
            $traversal['aria-labelledby']--;
            unset($traversal['source']);
        }
        // join with space according to doc 2.B.ii.c
        $joinedName = trim(implode(" ", $accNames));
        if(strlen($joinedName)>0) {
            return $joinedName;
        }   
    }



    if($node->hasAttribute("aria-label")) {
        $label = normalize($node->getAttribute("aria-label"));
        if(strlen($label)>0) {
            return $label;
        }   
    }

    // aria-labelledby and aria-label can still be used for naming hidden elements
    if(isElementHidden($node) && $traversal['aria-labelledby']==0) {
        return "";
    }

    if($node->hasAttribute("id") && $traversal['label']==0) {
        $id = $node->getAttribute("id");   
        $labelNodes = $xpath->query("//label[@for='".$id."']");
        $accNames = [];
        $traversal['source'] = $node;
        $traversal['label']++;
        foreach($labelNodes as $labelNode) {
            $accNames[] = normalize(computeAccessibleName($labelNode, $traversal));
        }
        $traversal['label']--;
        unset($traversal['source']);
        // join with space according to doc 2.B.ii.c
        $joinedName = trim(implode(" ", $accNames));
        if(strlen($joinedName)>0) {
            return $joinedName;
        }   
    }

    if($node->parentNode != null && $node->parentNode->tagName == "label") {
        // If a label is the direct parent node and current node is a form input
        $validInputElements = ["input","select","textarea","meter","progress"];
        if(in_array($tagName, $validInputElements)) {
            $accNames = [];
            $traversal['source'] = $node;
            $traversal['label']++;
            $labelNode = $node->parentNode;
            $accNames[] = normalize(computeAccessibleName($labelNode, $traversal));
            $traversal['label']--;
            unset($traversal['source']);
            // join with space according to doc 2.B.ii.c
            $joinedName = trim(implode(" ", $accNames));
            if(strlen($joinedName)>0) {
                return $joinedName;
            }   
        }
    }

    $role = '';
    if($node->hasAttribute("role")) {
        $role = $node->getAttribute("role");
    } else {
        $roles = \pest\aria\getRolesForElement($tagName);
        foreach($roles as $roleData) {
            if (isset($roleData["attribute"])) {
                $attrName = $roleData["attribute"]["name"];
                $attrValue = $roleData["attribute"]["value"];
                if ($node->getAttribute($attrName) == $attrValue) {
                    $role = $roleData["role"];
                    break;
                }
            } else {
                $role = $roleData["role"];
                break;
            }
        }
    }
    if (\pest\aria\isRoleSupportingNameFromContent($role)) {
        $accNames = [];
        foreach($node->childNodes as $childNode) {
            $accNames[] = normalize(computeAccessibleName($childNode, $traversal));
        }
        $joinedName = trim(implode(" ", $accNames));
        if(strlen($joinedName)>0) {
            return $joinedName;
        }   
    }


    if($tagName == "img" && $node->hasAttribute("alt")) {
        $alt = $node->getAttribute("alt");
        if(strlen($alt)>0) {
            return $alt;
        }  
    }

    // If naming from label then traverse its children
    if ($traversal['aria-labelledby'] || $traversal['label']) {
        $accNames = [];
        foreach($node->childNodes as $childNode) {
            // Traverse child nodes as long as they are not the source of recursion (avoid loops)
            if ($traversal['source'] != $childNode) {
                $accNames[] = normalize(computeAccessibleName($childNode, $traversal));
            }
        }
        $joinedName = trim(implode(" ", $accNames));
        if(strlen($joinedName)>0) {
            return $joinedName;
        }   
    }

    if($node->hasAttribute("title")) {
        $title = $node->getAttribute("title");
        if(strlen($title)>0) {
            return $title;
        }  
    }


    if($node->hasAttribute("placeholder")) {
        $placeholder = $node->getAttribute("placeholder");
        if(strlen($placeholder)>0) {
            return $placeholder;
        }  
    }

    return "";
}

function isElementHidden($node)
{
    // Check style for css which hides thee element
    $style = $node->getAttribute("style");
    if(strlen($style) > 0) {
        if(preg_match("/display\:\s*none\s*\;/i", $style)) {
            // display: none;
            return true;
        }
        if(preg_match("/visibility\:\s*hidden\s*\;/i", $style)) {
            //visibility: hidden;
            return true;
        }
    }
    // Is there a hidden attribute
    return getBoolAttribute($node, "hidden");
}

function getBoolAttribute($element, $attr)
{
    if($element->hasAttribute($attr)) {
        // attr selected returns 'selected'
        // attr selected='' return ''
        // attr selected='false' return false
        $val = $element->getAttribute($attr);
        return ($val == $attr || strtolower($val) == "true" || $val == "1");
    }
    return false;
}

function getInputValue($input) 
{
    $type = strtolower($input->getAttribute("type"));
    if ($type == "number") {
        // If number, coerce to number by adding zero
        $value = $input->hasAttribute("value") ? ($input->getAttribute("value") + 0) : null;
    } else if ($type == "checkbox") {
        //$value = $input->hasAttribute("checked");
        $value = getBoolAttribute($input, "checked");
    } else {
        $value = $input->getAttribute("value");
    }
    return $value;
}

function getSelectValue($select, $options = [])
{
    $displayValue = isset($options['displayValue']) ? $options['displayValue'] : false;
    //$multiple = $select->hasAttribute("multiple");
    $multiple = getBoolAttribute($select, "multiple");
    $dom = getDocument($select);    
    $xpath = new \DOMXPath($dom);
    
    $optionNodes = iterator_to_array($xpath->query("//option", $select));

    $selectedOptions = array_filter($optionNodes, function($node) { 
        //return $node->hasAttribute("selected"); 
        return getBoolAttribute($node, "selected");
    });

    // array_filter preserves the keys, so remove them
    $selectedOptions = array_values($selectedOptions);

    if ($multiple) {
        $values = array_map(function($node) use($displayValue) {
            if($displayValue) {
                return $node->textContent;
            }
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

    if($displayValue) {
        $value = $selectedOptions[0]->textContent;
    } else {
        $value = $selectedOptions[0]->getAttribute("value");
    }
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
            // What other elements have value attribute?
            if ($node->hasAttribute("value")) {
                $value = $node->getAttribute("value");
            } else {
                $value = $node->nodeValue;
            }
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


function cssSelectorToXPath($selector) 
{
    // TODO Handle more complicated patterns

    $parts = explode(",", $selector);
    $xpathParts = [];
    foreach($parts as $part) {
        $elem = "*";
        $part = trim($part);
        $attr = "";
        if ($part[0] == ".") {
            // class
            $class = substr($part, 1); 
            $attr = "[contains(concat(' ',@class,' '),' ".$class." ')]";
        } else if ($part[0] == "#") {
            // id
            $id = substr($part, 1);
            $attr = "[@id='".$id."']";
        } else {
            $elem = $part;
        }

        $xpathParts[] = "//".$elem.$attr;
    }

    $xpath = implode("|", $xpathParts);
    return $xpath;
}


function querySelectorAll($node, $selector) 
{
    $dom = getDocument($node);    
    $xpath = new \DOMXPath($dom);

    $q = cssSelectorToXPath($selector);
    $elements = iterator_to_array($xpath->query($q, $node));
    return $elements;
}


function querySelector($node, $selector) 
{
    $elements = querySelectorAll($node, $selector);
    if(count($elements) > 0) {
        return $elements[0];
    }
    return null;
}

