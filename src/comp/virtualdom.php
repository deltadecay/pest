<?php

namespace pest\comp;

require_once(__DIR__."/children.php");
require_once(__DIR__."/ref.php");
require_once(__DIR__."/../utils.php");
require_once(__DIR__."/../dom/dom.php");



$_VirtualDOM_instance = null;
function setVirtualDOM($root)
{
    global $_VirtualDOM_instance;
    $_VirtualDOM_instance = $root;
}
function getVirtualDOM()
{
    global $_VirtualDOM_instance;
    return $_VirtualDOM_instance;
}



class VirtualDOM 
{
    private $namespace = '';
    private $dom = null;
    private $childrenRegistry = [];
    private $refRegistry = [];

    public function __construct($namespace) 
    {
        $this->namespace = $namespace;
    }

    public function render($src)
    {
        $this->dom = $this->parseToNode($src);

        $this->transform($this->dom);

        
        $this->dom->formatOutput = false;
        $this->dom->preserveWhitespace = false;

        $dummy_root = $this->dom->documentElement;
        $rootname = $dummy_root->nodeName;
        $idattrStr = "";
        if($dummy_root->hasAttribute("id")) {
            $idattrStr = " id=\"".$dummy_root->getAttribute("id")."\"";
        }
        //$str = substr($this->dom->saveHtml(), strlen("<vrdomcomproot>"), -strlen("</vrdomcomproot>")-1);
        //$str = $this->dom->saveHtml();
        $str = substr($this->dom->saveHtml(), strlen("<".$rootname.$idattrStr.">"), -strlen("</".$rootname.">")-1);
        
        echo $str.PHP_EOL;
    }

    private function getDocument($node)
    {
        $dom = null;
        if($node instanceof \DOMDocument) {
            $dom = $node;
        } else if($node instanceof \DOMNode) {
            $dom = $node->ownerDocument;
        }
        return $dom;
    }

    private function registerChildren($children)
    {
        $id = count($this->childrenRegistry);
        $c = new Children($id, $children, $this);
        $this->childrenRegistry[$id] = $c;
        return $c;
    }

    public function registerRef($obj)
    {
        $id = count($this->refRegistry);
        $ref = new Ref($id, $obj);
        $this->refRegistry[$id] = $ref;
        return $ref;
    }


    public function decodeRef($str) 
    {
        $ref = null;
        $json = json_decode(\pest\utils\base64url_decode($str), true);
        if(isset($json["vrdomrefid"])) {
            $id = $json["vrdomrefid"];
            $ref = $this->refRegistry[$id];
        }
        return $ref;
    }

    public function getProps($node)
    {
        $props = [];
        if ($node->attributes != null) {
            foreach ($node->attributes as $attr) { 
                // If attribute value is a encoded ref, decode it and pass it as the value
                $value = $attr->nodeValue;
                $ref = $this->decodeRef($value);
                if($ref instanceof Ref) {
                    $value = $ref;
                }
                $props[$attr->localName] = $value; 
            } 
        }
        return $props;
    }

    private function transform($node, $level = 0)
    {
        if($level > 100) {
            throw new \Exception("DOM tree too deep?");
        }
        $dom = $this->getDocument($node);
        $nodeList = iterator_to_array($node->childNodes);
        $i = 0;
        while($i < count($nodeList)) {

            $comp = $nodeList[$i];

            $name = $comp->nodeName;

            $isUserDefined = false;
            try {
                // Check if there is a user defined function with the name of the tag
                $refFunction = new \ReflectionFunction($this->namespace."\\".$name);
                $isUserDefined = $refFunction->isUserDefined();
            } catch(\ReflectionException $e) {
                // $name doesn't exist as a function
            }
            /*if($name == "children") {
                $childrenId = $comp->attributes->getNamedItem("instanceid")->nodeValue;
                $children = $this->childrenRegistry[$childrenId]->nodeList;

                $newNodes = [];

                foreach($children as $child) {

                    $newNode = $dom->importNode($child, true);
                    $newNode = $node->insertBefore($newNode, $comp);
                    $newNodes[] = $newNode;
                
                }
                $node->removeChild($comp);
                array_splice($nodeList, $i, 1, $newNodes);
                $i--; // Back so we visit the new node
            } else*/ 
            if($isUserDefined && is_callable($this->namespace."\\".$name)) {
                // custom element
                if (\pest\dom\isHtmlTag($comp)) {
                    throw new \Exception("$name is an existing html tag, do not override it.");
                }
                
                $children = iterator_to_array($comp->childNodes);
                
                foreach($comp->childNodes as $child) {
                    $comp->removeChild($child);
                }

                $props = $this->getProps($comp);

                if(count($children) > 0) {
                    $props['children'] = $this->registerChildren($children);
                }

                setVirtualDOM($this);
                ob_start();
                //$ret_output = $name($props);
                $ret_output = call_user_func_array($this->namespace."\\".$name, array($props));
                $echoed_output = ob_get_clean();
                setVirtualDOM(null);
                
                $rendered_out = isset($ret_output) ? $ret_output : $echoed_output;

                //echo $rendered_out.PHP_EOL;

                $comp_dom = $this->parseToNode($rendered_out);
                if($comp_dom == null) {
                    throw new \Exception("Could not parse the rendered component ".$name);
                }

                /*
                // This only support the parsed comp_dom only has one root element
                $newNode = $dom->importNode($comp_dom->documentElement, true);
                // replace this custom element with the new dom tree 
                $node->replaceChild($newNode, $comp);
                $newNodes = [$newNode];
                */

                //$comp_root = $comp_dom->getElementsByTagName("vrdomcomproot")->item(0);
                $comp_root = $comp_dom->documentElement;

                

                $newNodes = [];
                // This supports multiple root elements per custom component
                foreach($comp_root->childNodes as $compDomChild) {
                    $newNode = $dom->importNode($compDomChild, true);
                    $node->insertBefore($newNode, $comp);
                    $newNodes[] = $newNode;
                }
                $node->removeChild($comp);

                array_splice($nodeList, $i, 1, $newNodes);
                $i--; // Back so we visit the new node
            } elseif($comp instanceof \DOMElement) {
                // Regular element, traverse down the tree
                $this->transform($comp, $level + 1);
            }

            $i++;
        }
    }

    private function parseToNode($src) 
    {
        //return \pest\dom\parse($src);
        
        libxml_use_internal_errors(true);
        $temp_dom = new \DOMDocument();
        $loadOk = $temp_dom->loadHTML("<vrdomcomproot>".$src."</vrdomcomproot>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        //foreach(libxml_get_errors() as $error) {
        //    echo "\t".$error->message.PHP_EOL;
        //}
        libxml_clear_errors();
        if (!$loadOk)
        {
            echo "Failed to load html".PHP_EOL;
            return null;
        }
        return $temp_dom;
    }
}
