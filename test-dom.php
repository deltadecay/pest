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
    
    $src = "<div><h1>Title</h1><h2>Subtitle</h2><span>Some data here</span><span class=\"greeting f50\"> hello <br> world </span><input type=\"text\" name=\"age\" value=\"50\"></div>";

    $dom = dom\parse($src);
    dom\debug($dom);

    $headers = dom\queryAllByRole($dom, "heading", ["name" => "/^title$/i"]);

    expect(count($headers))->toBe(1);

    $spanText = dom\queryAllByText($dom, "Some data here");
    expect(count($spanText))->toBe(1);

    $helloWorldText = dom\queryByText($dom, "/hello\s+world/i");
    expect($helloWorldText)->toBeInTheDocument();
    expect($helloWorldText)->toHaveClass('greeting');

    $holaText = dom\queryByText($dom, "/hola\s+mundo/i");
    expect($holaText)->not()->toBeInTheDocument();
});