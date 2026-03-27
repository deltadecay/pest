<?php

require_once(__DIR__."/pest-comp.php");

use function \pest\comp\makeRef;
use function \pest\comp\createRoot;

/*
ob_start();

//echo "Hello World";
?>
<p title="tooltip">Hello <span>World!</span> More text here.</p>

<?php
$out = ob_get_clean();
*/
/*
$xml = simplexml_load_string($out);
$json = json_encode($xml);

var_dump($xml->getName());
echo "xml=".strval($xml).PHP_EOL;
var_dump(iterator_to_array($xml->attributes()));
var_dump(iterator_to_array($xml->children()));

var_dump($xml);
//var_dump($json);
**/

/*
    ob_start();
    $render();
    $out = ob_get_clean();
*/

/*
function normalize($text)
{
    $t = $text;
    $t = preg_replace("/\s+/", " ", $t);
    $t = trim($t);
    return $t;
}
*/







/*

function Video($props) 
{

    $video = $props["video"];

    $str = <<<HTML
    <div>
      <Thumbnail video={$video} />
      <a href={video.url}>
        <h3>{video.title}</h3>
        <p>{video.description}</p>
      </a>
      <LikeButton video={video} />
    </div>
HTML;
    echo $str;
};

*/



function App($props) 
{
    $opt = ["show" => true, "class" => "zebra", "data" => [4, 5, 2, 7]];

    // More complex params need to have ref 
    $opt_ref = makeRef($opt);

    return (<<<HTML
<div>
    <ItemList options="$opt_ref">
        <Item value="hello" />
        <Item value="world" />
    </ItemList>
</div>
HTML
    );
}

function Itemlist($props)
{
    $children = $props["children"];
    $opt_ref = $props['options'];
    $opt = $opt_ref->current;
    $class = $opt["class"];

    //$items = $children->map(function($child,$node,$props,$index) { return "<li>".$child."</li>"; }, false);
    return (<<<HTML
<ul class="$class">
    $children
</ul>
HTML
    );
}

function Item($props) 
{

    return (<<<HTML
<li><div>$props[value]</div></li>
HTML
    );
}





$appRoot = createRoot();
$appRoot->render("<App />");



