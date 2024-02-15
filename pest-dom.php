<?php

namespace pest\dom;

require_once(__DIR__."/src/dom/dom.php");
require_once(__DIR__."/src/dom/domexpectation.php");

use \DOMXPath;



function expect($value)
{
    // This is the DOM specific expectation with matchers to support DOM nodes
    return new DOMExpectation($value);
}


// Returns a list of DOMNodes matching role
function queryAllByRole($container, $role, $options = array())
{
    $dom = getDocument($container);
    $xpath = new DOMXPath($dom);
    $found = [];

    // Find elements with aria role
    $nodelist = $xpath->query(".//*[@role=\"".$role."\"]", $container);
    foreach ($nodelist as $node) {
        if(!in_array($node, $found, true)) {
            $found[] = $node;
        }
    }

    // Find elements with implicit roles
    $elementsToFind = \pest\dom\getElementsForRole($role);
    if(is_array($elementsToFind)) {
        foreach($elementsToFind as $elem) {
            $name = $elem['name'];
            if(count($elem['attributes']) > 0) {
                // Search by name and attribute
                foreach($elem['attributes'] as $attr) {
                    $attrName = $attr['name'];
                    $attrValue = $attr['value'];

                    $nodelist = $xpath->query(".//".$name."[@".$attrName."=\"".$attrValue."\"]", $container);
                    foreach ($nodelist as $node) {
                        if(!in_array($node, $found, true)) {
                            $found[] = $node;
                        }
                    }
                }

            } else {
                // Just search by name
                $nodelist = $xpath->query(".//".$name, $container);
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
            $accessibleName = \pest\dom\computeAccessibleName($node);
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
    return expectAtMostOne($found, "role", $role);
}

// Get atleast one matching role, throws if nothing found
function getAllByRole($container, $role, $options = array())
{
    $found = queryAllByRole($container, $role, $options);
    return expectAtleastOne($found, "role", $role);
}

// Get one matching role, throws if nothing found, throws if many found
function getByRole($container, $role, $options = array())
{
    $found = queryAllByRole($container, $role, $options);
    return expectOnlyOne($found, "role", $role);
}



// Returns a list of DOMNodes matching text
function queryAllByText($container, $pattern, $options = array())
{
    $ignore = isset($options['ignore']) ? $options['ignore'] : "script, style";
    // TODO use selector to constrain the match
    //$selector = isset($options['selector']) ? $options['selector'] : "*";

    $dom = getDocument($container);
    $xpath = new DOMXPath($dom);

    $ignoredNodes = [];
    if (strlen($ignore) > 0) {
        $ignoreXPath = \pest\dom\cssSelectorToXPath($ignore);
        $ignoredNodes = iterator_to_array($xpath->query($ignoreXPath, $container));
    }

    // Find all nodes that have text content
    $nodelist = $xpath->query(".//*[string-length(text())>0]", $container);

    $found = [];
    foreach($nodelist as $node) {
        $tagName = strtolower($node->tagName);
        $firstNonEmptyNode = \pest\dom\getFirstNonEmptyChildNode($node);
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
    return expectAtMostOne($found, "text", $pattern);
}

// Get atleast one matching text, throws if nothing found
function getAllByText($container, $pattern, $options = array())
{
    $found = queryAllByText($container, $pattern, $options);
    return expectAtleastOne($found, "text", $pattern);
}

// Get one matching text, throws if nothing found, throws if many found
function getByText($container, $pattern, $options = array())
{
    $found = queryAllByText($container, $pattern, $options);
    return expectOnlyOne($found, "text", $pattern);
}





// Returns a list of DOMNodes with matching data-testid
function queryAllByTestId($container, $pattern, $options = array())
{
    $dom = getDocument($container);
    $xpath = new DOMXPath($dom);

    // Find all nodes that have attribute data-testid
    $nodelist = $xpath->query(".//*[@data-testid]", $container);

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
    return expectAtMostOne($found, "data-testid", $pattern);
}

// Get atleast one matching data-testid, throws if nothing found
function getAllByTestId($container, $pattern, $options = array())
{
    $found = queryAllByTestId($container, $pattern, $options);
    return expectAtleastOne($found, "data-testid", $pattern);
}

// Get one matching data-testid, throws if nothing found, throws if many found
function getByTestId($container, $pattern, $options = array())
{
    $found = queryAllByTestId($container, $pattern, $options);
    return expectOnlyOne($found, "data-testid", $pattern);
}


// Returns a list of DOMNodes with matching title attribute or title in svg
function queryAllByTitle($container, $pattern, $options = array())
{
    $dom = getDocument($container);
    $xpath = new DOMXPath($dom);

    // Find all nodes that have attribute title
    $nodelist = $xpath->query(".//*[@title]", $container);
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
    $nodelist = $xpath->query(".//svg//title", $container);
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
    return expectAtMostOne($found, "title", $pattern);
}

// Get atleast one matching title, throws if nothing found
function getAllByTitle($container, $pattern, $options = array())
{
    $found = queryAllByTitle($container, $pattern, $options);
    return expectAtleastOne($found, "title", $pattern);
}

// Get one matching title, throws if nothing found, throws if many found
function getByTitle($container, $pattern, $options = array())
{
    $found = queryAllByTitle($container, $pattern, $options);
    return expectOnlyOne($found, "title", $pattern);
}




// Returns a list of DOMNodes with matching alt attribute
function queryAllByAltText($container, $pattern, $options = array())
{
    $dom = getDocument($container);
    $xpath = new DOMXPath($dom);

    // Find all nodes that have attribute alt 
    $nodelist = $xpath->query(".//*[@alt]", $container);

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
    return expectAtMostOne($found, "alt", $pattern);
}

// Get atleast one matching alt attribute, throws if nothing found
function getAllByAltText($container, $pattern, $options = array())
{
    $found = queryAllByAltText($container, $pattern, $options);
    return expectAtleastOne($found, "alt", $pattern);
}

// Get one matching alt attribute, throws if nothing found, throws if many found
function getByAltText($container, $pattern, $options = array())
{
    $found = queryAllByAltText($container, $pattern, $options);
    return expectOnlyOne($found, "alt", $pattern);
}



// Returns a list of DOMNodes referenced by labels
function queryAllByLabelText($container, $pattern, $options = array())
{
    // TODO use selector to constrain the match
    //$selector = isset($options['selector']) ? $options['selector'] : "*";

    //$validInputElements = ["input","select","textarea","meter","progress"];

    $dom = getDocument($container);
    $xpath = new DOMXPath($dom);
    // Find all labels with text 
    $labelNodes = $xpath->query(".//label[string-length(text())>0]", $container);

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
                $inputNodes = $xpath->query(".//*[@id=\"".$for."\"]");
                foreach($inputNodes as $inputNode) {
                    $inputTagName = strtolower($inputNode->tagName);
                    if(!in_array($inputNode, $found, true) && isValidInputElements($inputTagName)) {
                        $found[] = $inputNode;
                    }
                }
            }
            if(strlen($id) > 0) {
                // "id" attribute, must find an input with matching aria-labelledby
                // Note! aria-labelledby can be a space separated list
                //$inputNodes = $xpath->query("//*[@aria-labelledby='".$id."']");
                $inputNodes = $xpath->query(".//*[contains(concat(\" \",normalize-space(@aria-labelledby),\" \"),\" ".$id." \")]");
                foreach($inputNodes as $inputNode) {
                    $inputTagName = strtolower($inputNode->tagName);
                    if(!in_array($inputNode, $found, true) && isValidInputElements($inputTagName)) {
                        $found[] = $inputNode;
                    }
                }
            }

            // Any child input nodes to this label
            $inputNodes = $xpath->query(".//*", $labelNode);
            foreach($inputNodes as $inputNode) {
                $inputTagName = strtolower($inputNode->tagName);
                if(!in_array($inputNode, $found, true) && isValidInputElements($inputTagName)) {
                    $found[] = $inputNode;
                }
            }
        }
    }

    // Any element with attribute aria-label, this can be used on any interactive element not
    // just those constrained by label elements
    $inputNodes = $xpath->query(".//*[@aria-label]", $container);
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
    return expectAtMostOne($found, "label", $pattern);
}

// Get atleast one element with matching label, throws if nothing found
function getAllByLabelText($container, $pattern, $options = array())
{
    $found = queryAllByLabelText($container, $pattern, $options);
    return expectAtleastOne($found, "label", $pattern);
}

// Get one element with matching label, throws if nothing found, throws if many found
function getByLabelText($container, $pattern, $options = array())
{
    $found = queryAllByLabelText($container, $pattern, $options);
    return expectOnlyOne($found, "label", $pattern);
}



// Returns a list of DOMNodes with matching placeholder
function queryAllByPlaceholderText($container, $pattern, $options = array())
{
    $dom = getDocument($container);
    $xpath = new DOMXPath($dom);

    // Find all input/textarea that have attribute placeholder
    $nodelist = $xpath->query(".//input[@placeholder]|.//textarea[@placeholder]", $container);

    $found = [];
    foreach($nodelist as $node) {
        if($node instanceof \DOMElement) {
            $placeholder = $node->getAttribute("placeholder");
            $hasMatch = \pest\utils\hasTextMatch($pattern, $placeholder, $options);
            if($hasMatch) {
                if(!in_array($node, $found, true)) {
                    $found[] = $node;
                }
            }
        }
    }
    return $found;
}


// Returns elements with matching placeholder if found, null if not found, throws if many found
function queryByPlaceholderText($container, $pattern, $options = array())
{
    $found = queryAllByPlaceholderText($container, $pattern, $options);
    return expectAtMostOne($found, "placeholder", $pattern);
}

// Get atleast one element with matching placeholder, throws if nothing found
function getAllByPlaceholderText($container, $pattern, $options = array())
{
    $found = queryAllByPlaceholderText($container, $pattern, $options);
    return expectAtleastOne($found, "placeholder", $pattern);
}

// Get one element with matching placeholder, throws if nothing found, throws if many found
function getByPlaceholderText($container, $pattern, $options = array())
{
    $found = queryAllByPlaceholderText($container, $pattern, $options);
    return expectOnlyOne($found, "placeholder", $pattern);
}


// Returns a list of DOMNodes with matching display value
function queryAllByDisplayValue($container, $pattern, $options = array())
{
    $dom = getDocument($container);
    $xpath = new DOMXPath($dom);

    // Find all input/textarea 
    $nodelist = $xpath->query(".//input[@value]|.//textarea", $container);
    $found = [];
    foreach($nodelist as $node) {
        if($node instanceof \DOMElement) {
            $displayValue = \pest\dom\getElementValue($node);
            $hasMatch = \pest\utils\hasTextMatch($pattern, $displayValue, $options);
            if($hasMatch) {
                if(!in_array($node, $found, true)) {
                    $found[] = $node;
                }
            }
        }
    }
    // Find all selected options, but we do not vant the value attribute but instead the displayed value
    // ie. the text content of option
    $nodelist = $xpath->query(".//select/option[@selected]/..", $container);
    foreach($nodelist as $node) {
        if($node instanceof \DOMElement) {
            $displayValue = \pest\dom\getSelectValue($node, ["displayValue" => true]);
            $hasMatch = \pest\utils\hasTextMatch($pattern, $displayValue, $options);
            if($hasMatch) {
                if(!in_array($node, $found, true)) {
                    $found[] = $node;
                }
            }
        }
    }

    return $found;
}


// Returns elements with matching display value if found, null if not found, throws if many found
function queryByDisplayValue($container, $pattern, $options = array())
{
    $found = queryAllByDisplayValue($container, $pattern, $options);
    return expectAtMostOne($found, "display value", $pattern);
}

// Get atleast one element with matching display value, throws if nothing found
function getAllByDisplayValue($container, $pattern, $options = array())
{
    $found = queryAllByDisplayValue($container, $pattern, $options);
    return expectAtleastOne($found, "display value", $pattern);
}

// Get one element with matching display value, throws if nothing found, throws if many found
function getByDisplayValue($container, $pattern, $options = array())
{
    $found = queryAllByDisplayValue($container, $pattern, $options);
    return expectOnlyOne($found, "display value", $pattern);
}

