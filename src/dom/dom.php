<?php

namespace pest\dom;

require_once(__DIR__."/aria.php");
require_once(__DIR__."/../utils.php");

use function \pest\utils\normalize;

function loadDOM($src) 
{
    $id = "_pest_root";
    //$dom = new \DOMDocument();
    //$dom->loadHTML($src);  
    libxml_use_internal_errors(true);

    // Load content to a dummy root div and specify encoding with a meta tag
    $temp_dom = new \DOMDocument();
    $loadOk = $temp_dom->loadHTML("<meta http-equiv='Content-Type' content='charset=utf-8' /><div id=\"$id\">$src</div>");
    /*foreach(libxml_get_errors() as $error) {
        echo "\t".$error->message.PHP_EOL;
    }*/
    libxml_clear_errors();
    if (!$loadOk)
    {
        echo "Failed to load html".PHP_EOL;
        return null;
    }

    // As loadHTML() adds a DOCTYPE as well as <html> and <body> tag, 
    // create another DOMDocument and import just the nodes we want
    $dom = new \DOMDocument();
    $first_div = $temp_dom->getElementsByTagName('div')[0];
    // Imports and returns the copy
    $first_div_node = $dom->importNode($first_div, true);
    // Add it to the new dom
    $dom->appendChild($first_div_node);
    return $dom;
}

function outputDOM(\DOMDocument $dom)
{
    $id = "_pest_root";
    $dom->formatOutput = true;
    // Remove the dummy root that we added in parse
    $str = substr($dom->saveHtml(), strlen("<div id=\"$id\">"), -(strlen("</div>")+1));
    return $str;
}


function expectAtMostOne($found, $type, $pattern)
{
    $n = count($found);
    if ($n == 0) {
        return null;
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected at most one element with $type $pattern, but found $n.");
}

function expectAtleastOne($found, $type, $pattern)
{
    if(count($found) == 0) {
        throw new Exception("Expected atleast one element with $type $pattern, but found none.");
    }
    return $found;
}

function expectOnlyOne($found, $type, $pattern)
{
    $n = count($found);
    if ($n == 0) {
        throw new Exception("Expected one element with $type $pattern, but found none.");
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected one element with $type $pattern, but found $n.");
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
        $roles = \pest\dom\getRolesForElement($tagName);
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
    if (\pest\dom\isRoleSupportingNameFromContent($role)) {
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
    
    // Collapse and trim whitespace
    $selector = normalize($selector);

    $pathAlts = explode(",", $selector);
    $xpathParts = [];
    foreach($pathAlts as $pathAlt) {
        $pathAlt = trim($pathAlt);

        // Split paths on space to get the different parts
        $parts = explode(" ", $pathAlt);

        $partPath = "";
        $pathSep = "//";
        foreach($parts as $part) {
            $part = trim($part);
            if ($part == ">") {
                $pathSep = "/";
                continue;
            }

            $elemparts = explode(".", $part); 
            $elem = $elemparts[0]; // First is the elem

            $hashPos = stripos($elem, "#");
            $attr = "";
            if ($hashPos !== false) {
                $id = substr($elem, $hashPos + 1);
                $elem = substr($elem, 0, $hashPos);
                $attr .= "[@id='".$id."']";
                
            }
            if($elem == "") {
                $elem = "*";
            }
            $classes = array_slice($elemparts, 1);
            //}

            foreach($classes as $class) {
                $hashPos = stripos($class, "#");
                $id = "";
                if ($hashPos !== false) {
                    $id = substr($class, $hashPos + 1);
                    $class = substr($class, 0, $hashPos);   
                }
                if($class != "") { 
                    $attr .= "[contains(concat(' ',normalize-space(@class),' '),' ".$class." ')]";
                }
                if($id != "") {
                    $attr .= "[@id='".$id."']";
                }
            }

            $partPath .= $pathSep.$elem.$attr;
            $pathSep = "//"; // reset to //. We need > to set to /
        }
        $xpathParts[] = $partPath;
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

