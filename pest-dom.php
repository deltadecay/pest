<?php

namespace pest\dom;

require_once(__DIR__."/aria.php");
require_once(__DIR__."/utils.php");

use \DOMDocument;
use \DOMXPath;
use \Exception;

function parse($src)
{
    $id = "_pest_root";
    //$dom = new DOMDocument();
    //$dom->loadHTML($src);  
    libxml_use_internal_errors(true);

    // Load content to a dummy root div and specify encoding with a meta tag
    $temp_dom = new DOMDocument();
    $loadOk = $temp_dom->loadHTML("<meta http-equiv='Content-Type' content='charset=utf-8' /><div id=\"$id\">$src</div>");
    /*foreach(libxml_get_errors() as $error) {
        echo "\t".$error->message.PHP_EOL;
    }*/
    libxml_clear_errors();
    if (!$loadOk)
    {
        return null;
    }

    // As loadHTML() adds a DOCTYPE as well as <html> and <body> tag, 
    // create another DOMDocument and import just the nodes we want
    $dom = new DOMDocument();
    $first_div = $temp_dom->getElementsByTagName('div')[0];
    // Imports and returns the copy
    $first_div_node = $dom->importNode($first_div, true);
    // Add it to the new dom
    $dom->appendChild($first_div_node);
    return $dom;
}

function debug($dom) 
{
    $id = "_pest_root";
    $dom->formatOutput = true;
    // Remove the dummy root that we added in parse
    $str = substr($dom->saveHtml(), strlen("<div id=\"$id\">"), -(strlen("</div>")+1));
    echo $str.PHP_EOL;
}


// Returns a list of DOMNodes matching role
function queryAllByRole($container, $role, $options = array())
{
    /*if($container instanceof DOMDocument) {
        $dom = $container;
    } else {
        $dom = $container->ownerDocument;    
    }*/
    $dom = \pest\utils\getDocument($container);
    $xpath = new DOMXPath($dom);
    $found = [];

    // Find elements with aria role
    $nodelist = $xpath->query("//*[@role='".$role."']", $container);
    foreach ($nodelist as $node) {
        if(!in_array($node, $found, true)) {
            $found[] = $node;
        }
    }

    // Find elements with implicit roles
    $elementsToFind = \pest\aria\getRoleElementsMap()[$role];
    if(is_array($elementsToFind)) {
        foreach($elementsToFind as $elem) {
            $name = $elem['name'];
            if(count($elem['attributes']) > 0) {
                // Search by name and attribute
                foreach($elem['attributes'] as $attr) {
                    $attrName = $attr['name'];
                    $attrValue = $attr['value'];

                    $nodelist = $xpath->query("//".$name."[@".$attrName."='".$attrValue."']", $container);
                    foreach ($nodelist as $node) {
                        if(!in_array($node, $found, true)) {
                            $found[] = $node;
                        }
                    }
                }

            } else {
                // Just search by name
                $nodelist = $xpath->query("//".$name, $container);
                foreach ($nodelist as $node) {
                    if(!in_array($node, $found, true)) {
                        $found[] = $node;
                    }
                }
            }
        }
    }

    // If options name set then use it to filter with matching text content
    if(isset($options['name']) && strlen($options['name'])>0)
    {
        $matches = [];
        $pattern = $options['name'];
        foreach($found as $node) {
            $accessibleName = \pest\utils\computeAccessibleName($node);
            // the exact option has no effect on matching accesible names with the name pattern 
            $hasMatch = \pest\utils\hasTextMatch($pattern, $accessibleName);
            if($hasMatch) {
                $matches[] = $node;
            }
        }
        $found = $matches;
    }

    return $found;
}

// Returns matching role if found, null if not found, throws if many found
function queryByRole($container, $role, $options = array())
{
    $found = queryAllByRole($container, $role, $options);
    $n = count($found);
    if ($n == 0) {
        return null;
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected at most one element with role $role, but found $n.");
}

// Get atleast one matching role, throws if nothing found
function getAllByRole($container, $role, $options = array())
{
    $found = queryAllByRole($container, $role, $options);
    if(count($found) == 0) {
        throw new Exception("Exepected atleast one element with role $role, but found none.");
    }
    return $found;
}

// Get one matching role, throws if nothing found, throws if many found
function getByRole($container, $role, $options = array())
{
    $found = queryAllByRole($container, $role, $options);
    $n = count($found);
    if ($n == 0) {
        throw new Exception("Expected one element with role $role, but found none.");
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected one element with role $role, but found $n.");
}



// Returns a list of DOMNodes matching text
function queryAllByText($container, $pattern, $options = array())
{
    $ignore = isset($options['ignore']) ? $options['ignore'] : "script, style";
    // TODO use selector to constrain the match
    //$selector = isset($options['selector']) ? $options['selector'] : "*";

    /*if($container instanceof DOMDocument) {
        $dom = $container;
    } else {
        $dom = $container->ownerDocument;    
    }*/
    $dom = \pest\utils\getDocument($container);
    $xpath = new DOMXPath($dom);

    $ignoredNodes = [];
    if (strlen($ignore) > 0) {
        $ignoreXPath = \pest\utils\cssSelectorToXPath($ignore);
        $ignoredNodes = iterator_to_array($xpath->query($ignoreXPath, $container));
    }

    // Find all nodes that have text content
    $nodelist = $xpath->query("//*[string-length(text())>0]", $container);

    $found = [];
    foreach($nodelist as $node) {
        $tagName = strtolower($node->tagName);
        $firstNonEmptyNode = \pest\utils\getFirstNonEmptyChildNode($node);
        if($firstNonEmptyNode instanceof \DOMText) {
            // The first non empty node is a DOMText, which means content begins with text.
            // Thus wee can be sure that contents of the node can be considered as text
            $nodeText = $node->textContent;
            $hasMatch = \pest\utils\hasTextMatch($pattern, $nodeText, $options);
            if($hasMatch && !in_array($node, $ignoredNodes, true)) {
                if(!in_array($node, $found, true)) {
                    $found[] = $node;
                }
            }
        }
    }
    return $found;
}


// Returns matching text if found, null if not found, throws if many found
function queryByText($container, $pattern, $options = array())
{
    $found = queryAllByText($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        return null;
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected at most one element with text $pattern, but found $n.");
}

// Get atleast one matching text, throws if nothing found
function getAllByText($container, $pattern, $options = array())
{
    $found = queryAllByText($container, $pattern, $options);
    if(count($found) == 0) {
        throw new Exception("Exepected atleast one element with text $pattern, but found none.");
    }
    return $found;
}

// Get one matching text, throws if nothing found, throws if many found
function getByText($container, $pattern, $options = array())
{
    $found = queryAllByText($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        throw new Exception("Expected one element with text $pattern, but found none.");
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected one element with text $pattern, but found $n.");
}





// Returns a list of DOMNodes with matching data-testid
function queryAllByTestId($container, $pattern, $options = array())
{
    /*if($container instanceof DOMDocument) {
        $dom = $container;
    } else {
        $dom = $container->ownerDocument;    
    }*/
    $dom = \pest\utils\getDocument($container);
    $xpath = new DOMXPath($dom);

    // Find all nodes that have attribute data-testid
    $nodelist = $xpath->query("//*[@data-testid]", $container);

    $found = [];
    foreach($nodelist as $node) {
        if($node instanceof \DOMElement) {
            $testId = $node->getAttribute("data-testid");
            $hasMatch = \pest\utils\hasTextMatch($pattern, $testId, $options);
            if($hasMatch) {
                if(!in_array($node, $found, true)) {
                    $found[] = $node;
                }
            }
        }
    }
    return $found;
}


// Returns matching data-testid if found, null if not found, throws if many found
function queryByTestId($container, $pattern, $options = array())
{
    $found = queryAllByTestId($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        return null;
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected at most one element with data-testid $pattern, but found $n.");
}

// Get atleast one matching data-testid, throws if nothing found
function getAllByTestId($container, $pattern, $options = array())
{
    $found = queryAllByTestId($container, $pattern, $options);
    if(count($found) == 0) {
        throw new Exception("Exepected atleast one element with data-testid $pattern, but found none.");
    }
    return $found;
}

// Get one matching data-testid, throws if nothing found, throws if many found
function getByTestId($container, $pattern, $options = array())
{
    $found = queryAllByTestId($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        throw new Exception("Expected one element with data-testid $pattern, but found none.");
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected one element with data-testid $pattern, but found $n.");
}


// Returns a list of DOMNodes with matching title attribute or title in svg
function queryAllByTitle($container, $pattern, $options = array())
{
    /*if($container instanceof DOMDocument) {
        $dom = $container;
    } else {
        $dom = $container->ownerDocument;    
    }*/
    $dom = \pest\utils\getDocument($container);
    $xpath = new DOMXPath($dom);

    // Find all nodes that have attribute title
    $nodelist = $xpath->query("//*[@title]", $container);
    $found = [];
    foreach($nodelist as $node) {
        if($node instanceof \DOMElement) {
            $title = $node->getAttribute("title");
            $hasMatch = \pest\utils\hasTextMatch($pattern, $title, $options);
            if($hasMatch) {
                if(!in_array($node, $found, true)) {
                    $found[] = $node;
                }
            }
        }
    }

    // Find all title nodes which are descendants of svg
    $nodelist = $xpath->query("//svg//title", $container);
    foreach($nodelist as $node) {   
        $text = $node->textContent;
        $hasMatch = \pest\utils\hasTextMatch($pattern, $text, $options);
        if($hasMatch) {
            if(!in_array($node, $found, true)) {
                $found[] = $node;
            }
        }
    }

    return $found;
}



// Returns matching title if found, null if not found, throws if many found
function queryByTitle($container, $pattern, $options = array())
{
    $found = queryAllByTitle($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        return null;
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected at most one element with title $pattern, but found $n.");
}

// Get atleast one matching title, throws if nothing found
function getAllByTitle($container, $pattern, $options = array())
{
    $found = queryAllByTitle($container, $pattern, $options);
    if(count($found) == 0) {
        throw new Exception("Exepected atleast one element with title $pattern, but found none.");
    }
    return $found;
}

// Get one matching title, throws if nothing found, throws if many found
function getByTitle($container, $pattern, $options = array())
{
    $found = queryAllByTitle($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        throw new Exception("Expected one element with title $pattern, but found none.");
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected one element with title $pattern, but found $n.");
}




// Returns a list of DOMNodes with matching alt attribute
function queryAllByAltText($container, $pattern, $options = array())
{
    /*if($container instanceof DOMDocument) {
        $dom = $container;
    } else {
        $dom = $container->ownerDocument;    
    }*/
    $dom = \pest\utils\getDocument($container);
    $xpath = new DOMXPath($dom);

    // Find all nodes that have attribute alt 
    $nodelist = $xpath->query("//*[@alt]", $container);

    $found = [];
    foreach($nodelist as $node) {
        $tagName = strtolower($node->tagName);
        // alt attribute only accepted in img, input, area
        if(($node instanceof \DOMElement) && in_array($tagName, ["img", "input", "area"])) {
            $alt = $node->getAttribute("alt");
            $hasMatch = \pest\utils\hasTextMatch($pattern, $alt, $options);
            if($hasMatch) {
                if(!in_array($node, $found, true)) {
                    $found[] = $node;
                }
            }
        }
    }
    return $found;
}



// Returns matching alt attribute if found, null if not found, throws if many found
function queryByAltText($container, $pattern, $options = array())
{
    $found = queryAllByAltText($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        return null;
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected at most one element with alt $pattern, but found $n.");
}

// Get atleast one matching alt attribute, throws if nothing found
function getAllByAltText($container, $pattern, $options = array())
{
    $found = queryAllByAltText($container, $pattern, $options);
    if(count($found) == 0) {
        throw new Exception("Exepected atleast one element with alt $pattern, but found none.");
    }
    return $found;
}

// Get one matching alt attribute, throws if nothing found, throws if many found
function getByAltText($container, $pattern, $options = array())
{
    $found = queryAllByAltText($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        throw new Exception("Expected one element with alt $pattern, but found none.");
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected one element with alt $pattern, but found $n.");
}



// Returns a list of DOMNodes referenced by labels
function queryAllByLabelText($container, $pattern, $options = array())
{
    // TODO use selector to constrain the match
    //$selector = isset($options['selector']) ? $options['selector'] : "*";

    $validInputElements = ["input","select","textarea","meter","progress"];

    $dom = \pest\utils\getDocument($container);
    $xpath = new DOMXPath($dom);
    // Find all labels with text 
    $labelNodes = $xpath->query("//label[string-length(text())>0]", $container);

    $found = [];
    foreach($labelNodes as $labelNode) {
        $tagName = strtolower($labelNode->tagName);
        $text = $labelNode->textContent; 
        $hasMatch = \pest\utils\hasTextMatch($pattern, $text, $options);

        if(($labelNode instanceof \DOMElement) && $hasMatch) {
            $for = $labelNode->getAttribute("for");
            $id = $labelNode->getAttribute("id");
            if(strlen($for) > 0) {
                // "for" attribute, must find an input with matching id
                $inputNodes = $xpath->query("//*[@id='".$for."']");
                foreach($inputNodes as $inputNode) {
                    $inputTagName = strtolower($inputNode->tagName);
                    if(!in_array($inputNode, $found, true) && in_array($inputTagName, $validInputElements)) {
                        $found[] = $inputNode;
                    }
                }
            }
            if(strlen($id) > 0) {
                // "id" attribute, must find an input with matching aria-labelledby
                $inputNodes = $xpath->query("//*[@aria-labelledby='".$id."']");
                foreach($inputNodes as $inputNode) {
                    $inputTagName = strtolower($inputNode->tagName);
                    if(!in_array($inputNode, $found, true) && in_array($inputTagName, $validInputElements)) {
                        $found[] = $inputNode;
                    }
                }
            }

            // Any child input nodes to this label
            $inputNodes = $xpath->query("//*", $labelNode);
            foreach($inputNodes as $inputNode) {
                $inputTagName = strtolower($inputNode->tagName);
                if(!in_array($inputNode, $found, true) && in_array($inputTagName, $validInputElements)) {
                    $found[] = $inputNode;
                }
            }
        }
    }

    // Any element with attribute aria-label, this can be used on any interactive element not
    // just those constrained by label elements
    $inputNodes = $xpath->query("//*[@aria-label]", $container);
    foreach($inputNodes as $inputNode) {
        $inputTagName = strtolower($inputNode->tagName);
        $ariaLabel = $inputNode->getAttribute("aria-label");
        $hasMatch = \pest\utils\hasTextMatch($pattern, $ariaLabel, $options);
        if($hasMatch) {
            if(!in_array($inputNode, $found, true)) {
                $found[] = $inputNode;
            }
        }
    }

    return $found;
}



// Returns elements with matching labels if found, null if not found, throws if many found
function queryByLabelText($container, $pattern, $options = array())
{
    $found = queryAllByLabelText($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        return null;
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected at most one element with label $pattern, but found $n.");
}

// Get atleast one element with matching label, throws if nothing found
function getAllByLabelText($container, $pattern, $options = array())
{
    $found = queryAllByLabelText($container, $pattern, $options);
    if(count($found) == 0) {
        throw new Exception("Exepected atleast one element with label $pattern, but found none.");
    }
    return $found;
}

// Get one element with matching label, throws if nothing found, throws if many found
function getByLabelText($container, $pattern, $options = array())
{
    $found = queryAllByLabelText($container, $pattern, $options);
    $n = count($found);
    if ($n == 0) {
        throw new Exception("Expected one element with label $pattern, but found none.");
    } 
    if ($n == 1) {
        return $found[0];
    } 
    throw new Exception("Expected one element with label $pattern, but found $n.");
}

