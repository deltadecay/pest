<?php 
namespace DOMTests;

require_once(__DIR__."/pest.php");
require_once(__DIR__."/pest-dom.php");


use \Exception;

use function \pest\test;
use function \pest\expect;
use function \pest\mockfn;
use \pest\dom;

test("dom", function() {
    
    $src = "<div><h1>Title</h1><h2>Subtitle</h2><span>Some data here</span></div>";

    $dom = dom\parse($src);

    //var_dump($dom);
    dom\debug($dom);

    $headers = dom\queryAllByRole($dom, "heading", ["name" => "/^title$/i"]);

    expect(count($headers))->toBe(1);
});