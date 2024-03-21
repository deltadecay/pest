<?php

namespace pest\comp;

require_once(__DIR__."/src/comp/virtualdom.php");
require_once(__DIR__."/src/dom/dom.php");




function makeRef($obj_arr)
{
    $vdom = getVirtualDOM();
    if($vdom == null) {
        throw new \Exception("No current virtual dom. Only call this from inside a component function.");
    }
    return $vdom->registerRef($obj_arr);
}


function createRoot($namespace = '')
{
    $vdom = new VirtualDOM($namespace);
    return $vdom;
}

$_lastRenderOutput = "";

function render($compSrc, $namespace = '') 
{
    global $_lastRenderOutput;
    $vdom = createRoot($namespace);
    
    ob_start();
    $vdom->render($compSrc);
    $rendered_output = ob_get_clean();
    $_lastRenderOutput = $rendered_output;

    $dom = \pest\dom\parse($rendered_output);

    return $dom;
}

function debug()
{
    global $_lastRenderOutput;
    echo $_lastRenderOutput;
}