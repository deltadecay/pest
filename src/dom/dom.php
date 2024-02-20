<?php

namespace pest\dom;

require_once(__DIR__."/aria.php");
require_once(__DIR__."/../utils.php");

use function \pest\utils\normalize;
use \Exception;

function parse($src) 
{
    $id = "_pest_root";
    //$dom = new \DOMDocument();
    //$dom->loadHTML($src);  
    libxml_use_internal_errors(true);

    // Load content to a dummy root and specify encoding with a meta tag
    $temp_dom = new \DOMDocument();
    $loadOk = $temp_dom->loadHTML("<meta http-equiv='Content-Type' content='charset=utf-8' /><dummyroot id=\"$id\">$src</dummyroot>");
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
    $first_div = iterator_to_array($temp_dom->getElementsByTagName('dummyroot'))[0];
    // Imports and returns the copy
    $first_div_node = $dom->importNode($first_div, true);
    // Add it to the new dom
    $dom->appendChild($first_div_node);
    return $dom;
}

function debug(\DOMDocument $dom)
{
    $id = "_pest_root";
    $dom->formatOutput = true;
    // Remove the dummy root that we added in parse
    $str = substr($dom->saveHtml(), strlen("<dummyroot id=\"$id\">"), -(strlen("</dummyroot>")+1));
    echo $str;
}


function expectAtMostOne($found, $type="some", $pattern="value")
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

function expectAtleastOne($found, $type="some", $pattern="value")
{
    if(count($found) == 0) {
        throw new Exception("Expected atleast one element with $type $pattern, but found none.");
    }
    return $found;
}

function expectOnlyOne($found, $type="some", $pattern="value")
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

/**
 * Get the DOM document for a node.
 * @param \DOMNode The node
 * @return \DOMDocument The document
 */
function getDocument(\DOMNode $node)
{
    if($node instanceof \DOMDocument) {
        $doc = $node;
    } else {
        $doc = $node->ownerDocument;    
    }
    if($doc == null) {
        throw new Exception("No owner document for node");
    }
    return $doc;
}

function computeAccessibleName(\DOMNode $node, $traversal = []) 
{
    // TODO See https://www.w3.org/TR/accname-1.1/#mapping_additional_nd
    $dom = getDocument($node);    
    $xpath = new \DOMXPath($dom);

    if ($node instanceof \DOMText) {
        $text = normalize($node->textContent);
        return $text;
    }

    if (!($node instanceof \DOMElement)) {
        return "";
    }
    $tagName = strtolower($node->tagName);
    $accName = "";

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

    if($node->parentNode != null && ($node->parentNode instanceof \DOMElement) && 
        $node->parentNode->tagName == "label") {
        // If a label is the direct parent node and current node is a form input
        if(isValidInputElements($tagName)) {
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
        $roles = getRolesForElement($tagName);
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
    if (isRoleSupportingNameFromContent($role)) {
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

function isValidInputElements($node)
{
    if($node instanceof \DOMElement) {
        $node = strtolower($node->tagName);
    } else {
        $node = strtolower("$node");
    }
    return in_array($node, ["input", "select", "textarea", "meter", "progress"]);
}

function isElementHidden(\DOMElement $node)
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

function getBoolAttribute(\DOMElement $element, $attr)
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

function getInputValue(\DOMElement $input) 
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

function getSelectValue(\DOMElement $select, $options = [])
{
    $displayValue = isset($options['displayValue']) ? $options['displayValue'] : false;
    //$multiple = $select->hasAttribute("multiple");
    $multiple = getBoolAttribute($select, "multiple");
    $dom = getDocument($select);    
    $xpath = new \DOMXPath($dom);
    
    $optionNodes = iterator_to_array($xpath->query(".//option", $select));

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

function getElementValue($node, $options = [])
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
            $value = getSelectValue($node, $options);
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

function readToken($pos, $str, $delimiters = " ,.[]():#\"'+~>")
{
    $len = strlen($str);
    $token = "";
    while($pos < $len) {
        $c = $str[$pos];
        if(strpos($delimiters, $c) !== false) {
            break;
        }
        $token .= $c;
        $pos++;
    }
    return $token;
}

function selectNthFromExpression($expr, $posexpr = "position()")
{
    $expr = trim($expr);
    // Supported expressions:
    // even | odd
    // constant number
    // a*n op b , where op is + or -, b>=0 integer and a integer pos or neg
    $cond = "";
    unset($matches);
    if(preg_match("/(?P<evenodd>^odd|even$)|(?P<pos>^\d+$)|(?P<anplusb>(?P<a>-?\d*)n\s*((?P<op>[-+])\s*(?P<b>\d+))?)/", $expr, $matches)) {
        
        if($matches["evenodd"] == "odd") {
            $cond = "[($posexpr mod 2)=1]";
        } elseif($matches["evenodd"] == "even") {
            $cond = "[($posexpr mod 2)=0]";
        } elseif(strlen($matches["pos"]) > 0) {
            if($posexpr == "position()") {
                $cond = "[".intval($matches["pos"])."]";
            } else {
                $cond = "[$posexpr=".intval($matches["pos"])."]";
            }
        } elseif(strlen($matches["anplusb"]) > 0) {
            $a = 1;
            if($matches["a"] == "-") {
                $a = -1;
            } elseif($matches["a"] == "") {
                $a = 1;
            } else { 
                $a = intval($matches["a"]);
            }
            
            $b = strlen($matches["b"])>0 ? intval($matches["b"]) : 0;
            $op = $matches["op"] == "-" ? "-" : "+";
            
            if($a == 0 && $op == "+") {
                if($posexpr == "position()") {
                    $cond = "[".$b."]";
                } else {
                    $cond = "[$posexpr=".$b."]";
                }
            } elseif($a > 0 && $op == "+") {
                $cond = "[$posexpr>=$b and (($posexpr-$b) mod $a)=0]";
            } elseif($a > 0 && $op == "-") { 
                $cond = "[(($posexpr+$b) mod $a)=0]";
            } elseif($a < 0 && $op == "+") { 
                $cond = "[$posexpr<=$b and (($posexpr-$b) mod $a)=0]";
            } else {
                // -an - b always negative
                // this is an expression that results in no matches ever
                $cond = "[false]";
            }
            
            //     4n-1  2n-1  4n-4  2n+1  2n  2n+3  n-3  2n-3   -n + 3   -2n+4
            // n 0  -1   -1      -4   1    0   3     -3    -3     3         4
            // n 1   3    1       0   3    2   5     -2    -1     2         2 
            // n 2   7    3       4   5    4   7     -1     1     1         0
            // n 3  11    5       8   7    6   9      0     3     0        -2
        }
    }
    return $cond;
}


function cssSelectorToXPath($selector) 
{
    $str = $selector;
    // Collapse and trim whitespace so we have at most only one space separating characters
    $str = normalize($str);
    $len = strlen($str);
    $xpath = ".//";
    $elem = "*";
    $i = 0;
    while($i < $len) {
        $c = $str[$i];
        switch($c) {
            case ':': {
                // pseudo-class
                $name = readToken($i+1, $str);
                $i += strlen($name);
                // TODO support more of these
                switch($name) {
                    case "first-child":
                        $xpath .= $elem."[not(preceding-sibling::*)]";
                        $elem = "";
                        break;
                    case "last-child":
                        $xpath .= $elem."[not(following-sibling::*)]";
                        $elem = "";
                        break;
                    case "first-of-type":
                        $xpath .= $elem."[1]";
                        $elem = "";
                        break;
                    case "last-of-type":
                        $xpath .= $elem."[last()]";
                        $elem = "";
                        break;
                    case "enabled":
                        $xpath .= $elem."[@enabled]";
                        $elem = "";
                        break;
                    case "disabled":
                        $xpath .= $elem."[@disabled]";
                        $elem = "";
                        break;
                    case "checked":
                        $xpath .= $elem."[@selected or @checked]";
                        $elem = "";
                        break;
                    case "empty":
                        $xpath .= $elem."[not(*) and not(normalize-space())]";
                        $elem = "";
                        break;
                    case "nth-of-type":
                        if($str[$i+1] == "(") {
                            $expr = readToken($i+2, $str, ")");
                            $i += strlen($expr) + 2; // +2 for start and end parentheses ()
                            $cond = selectNthFromExpression($expr, "position()");
                            if(strlen($cond)> 0) {
                                $xpath .= $elem.$cond;
                                $elem = "";
                            }
                        }
                        break;
                    case "nth-child":
                        if($str[$i+1] == "(") {
                            $expr = readToken($i+2, $str, ")");
                            $i += strlen($expr) + 2; // +2 for start and end parentheses ()
                            $cond = selectNthFromExpression($expr, "(count(preceding-sibling::*)+1)");
                            if(strlen($cond)> 0) {
                                $xpath .= $elem.$cond;
                                $elem = "";
                            }
                        }
                        break;
                }
            }
            break;
            case ' ': {
                if(in_array($str[$i+1], ['>','+','~',','])) {
                    // Do nothing if next is one of the above, they will be handled in the cases below
                } else {
                    $xpath .= "//";
                    $elem = "*";
                }
            }
            break;
            case ',': {
                if($str[$i+1] == ' ') $i++;
                $xpath .= "|.//";
                $elem = "*";
            }
            break;
            case '>': {
                if($str[$i+1] == ' ') $i++;
                $xpath .= "/";
                $elem = "*";
            } 
            break;
            case '+': {
                if($str[$i+1] == ' ') $i++;
                $xpath .= "/following-sibling::*[1]/self::";
                $elem = "*";
            }
            break;
            case '~': {
                if($str[$i+1] == ' ') $i++;
                $xpath .= "/following-sibling::";
                $elem = "*";
            }
            break;
            /*case '"': {
                $state['string'] = 'string';
                $name = readToken($i+1, $str, "\"");
                $i += strlen($name) + 1; // +1 for ending "
            }
            break;
            case '\'': {
                $state['string'] = 'string';
                $name = readToken($i+1, $str, "'");
                $i += strlen($name) + 1; // +1 for ending '
            }
            break;*/
            case '[': {
                // attribute
                $attrSpec = readToken($i+1, $str, "]");
                $i += strlen($attrSpec) + 1; // +1 for ending ]
                unset($matches);
                // parse attribute in the form [name op "value"]
                if(preg_match("/([a-zA-Z0-9_-]*)\s*(([\^\*\~\$|]*=)\s*[\"'](.*)[\"'])?/i", $attrSpec, $matches)) {
                    $attrName = $matches[1];
                    $xpath .= $elem;
                    if (count($matches) > 2) {
                        $attrOp = $matches[3];
                        $attrValue = $matches[4];
                        // Escape double quotes
                        $attrValue = str_replace('"', '\\"', $attrValue);
                        switch($attrOp[0]) {
                            case '=':
                                $xpath .= "[@".$attrName."=\"".$attrValue."\"]";
                                break;
                            case '*':
                                $xpath .= "[contains(@".$attrName.",\"".$attrValue."\")]";
                                break;
                            case '^':
                                $xpath .= "[starts-with(@".$attrName.",\"".$attrValue."\")]";
                                break;
                            case '|':
                                $xpath .= "[@".$attrName."=\"".$attrValue."\" or starts-with(@".$attrName.",\"".$attrValue."-\")]";
                                break;
                            case '$':
                                $xpath .= "[substring(@".$attrName.",string-length(@".$attrName.")-(string-length(\"".$attrValue."\")-1))=\"".$attrValue."\"]";
                                break;
                            case '~':
                                $xpath .= "[contains(concat(\" \",normalize-space(@".$attrName."),\" \"),\" ".$attrValue." \")]";
                                break;
                            default:
                                break;
                        }
                    } else {
                        $xpath .= "[@".$attrName."]";
                    }
                    $elem = "";
                }
            }
            break;
            case ".": {
                // class
                $name = readToken($i+1, $str);
                $i += strlen($name);
                if(strlen($name) > 0) {
                    $xpath .= $elem."[contains(concat(\" \",normalize-space(@class),\" \"),\" ".$name." \")]";
                    $elem = "";
                }
            }
            break;
            case "#": {
                // id
                $name = readToken($i+1, $str);
                $i += strlen($name);
                if(strlen($name) > 0) {
                    $xpath .= $elem."[@id=\"".$name."\"]";
                    $elem = "";
                }
            }
            break;
            default: {
                // No preceding special char, assume it is the name of an element
                $name = readToken($i, $str);
                $i += strlen($name);
                $xpath .= $name;
                if(strlen($name) > 0) {
                    // If we read a token it means we read an element
                    // but we didn't precede by a special char, so must compensate for the i++ later
                    $i--;
                }
                $elem = "";
            }
            break;
        }

        $i++;
    }

    return $xpath;
}


function querySelectorAll(\DOMNode $node, $selector) 
{
    $dom = getDocument($node);    
    $xpath = new \DOMXPath($dom);

    $q = cssSelectorToXPath($selector);
    $elements = iterator_to_array($xpath->query($q, $node));
    return $elements;
}


function querySelector(\DOMNode $node, $selector) 
{
    $elements = querySelectorAll($node, $selector);
    if(count($elements) > 0) {
        return $elements[0];
    }
    return null;
}

