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
    foreach(libxml_get_errors() as $error) {
        echo "\t".$error->message.PHP_EOL;
    }
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
    $elementsToFind = \pest\aria\getRoleElementsMap()[$role];

    if(!is_array($elementsToFind)) {
        return [];
    }

    if($container instanceof DOMDocument) {
        $dom = $container;
    } else {
        $dom = $container->$ownerDocument;    
    }
    $xpath = new DOMXPath($dom);

    $found = [];
    foreach($elementsToFind as $elem) {
        $name = $elem['name'];
        if(count($elem['attributes']) > 0) {
            // Search by name and attribute
            foreach($elem['attributes'] as $attr) {
                $attrName = $attr['name'];
                $attrValue = $attr['value'];

                $nodelist = $xpath->query("//".$name."[@".$attrName."='".$attrValue."']", $container);
                foreach ($nodelist as $node) {
                    $found[] = $node;
                }
            }

        } else {
            // Just search by name
            $nodelist = $xpath->query("//".$name, $container);
            foreach ($nodelist as $node) {
                $found[] = $node;
            }
        }
    }

    if(isset($options['name']) && strlen($options['name'])>0)
    {
        $matches = [];
        // If options name set then use it to match text content
        $pattern = $options['name'];
        foreach($found as $node) {
            $accessibleName = trim(\pest\utils\computeAccessibleName($node));
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



// Returns a list of DOMNodes matching pattern
function queryAllByText($container, $pattern, $options = array())
{
    if($container instanceof DOMDocument) {
        $dom = $container;
    } else {
        $dom = $container->$ownerDocument;    
    }
    $xpath = new DOMXPath($dom);

    // Find all nodes that have text content
    $nodelist = $xpath->query("//*[string-length(text())>0]", $container);

    $found = [];
    foreach($nodelist as $node) {
        $nodeText = trim($node->textContent);
        $hasMatch = \pest\utils\hasTextMatch($pattern, $nodeText);
        if($hasMatch) {
            $found[] = $node;
        }
    }
    return $found;
}


// Returns matching pattern if found, null if not found, throws if many found
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

// Get atleast one matching pattern, throws if nothing found
function getAllByText($container, $pattern, $options = array())
{
    $found = queryAllByText($container, $pattern, $options);
    if(count($found) == 0) {
        throw new Exception("Exepected atleast one element with text $pattern, but found none.");
    }
    return $found;
}

// Get one matching pattern, throws if nothing found, throws if many found
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
