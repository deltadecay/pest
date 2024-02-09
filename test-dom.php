<?php 
namespace DOMTests;

require_once(__DIR__."/pest.php");
require_once(__DIR__."/pest-dom.php");


use \Exception;

use function \pest\test;
use function \pest\expect;
use function \pest\mockfn;
use \pest\dom;

$src = <<<HTML
<div>
    <h1 role="heading">Title</h1>
    <h2>Subtitle</h2>
    <span>Some data here</span>
    <span class="greeting f50"> hello <br> world </span>
    <input type="text" name="age" value="50">
    <input type="checkbox" name="active" checked>
    <select name="choices" role="menu">
        <option value="1">First</option> 
        <option value="2" selected>Second</option> 
        <option value="3">Third</option> 
    </select>
</div>
HTML;

$dom = dom\parse($src);
dom\debug($dom);

test("query ByRole", function() use($dom) {

    $headers = dom\queryAllByRole($dom, "heading", ["name" => "/^title$/i"]);

    expect(count($headers))->toBe(1);

    $inputText = dom\getByRole($dom, "textbox");
    expect($inputText)->toBeInTheDocument();
    expect($inputText)->toHaveValue("50");

    $checkbox = dom\getByRole($dom, "checkbox");
    expect($checkbox)->toBeInTheDocument();
    expect($checkbox)->toHaveValue(true);
});

test("query ByText", function() use($dom) {
    
    $spanText = dom\queryAllByText($dom, "Some data here");
    expect(count($spanText))->toBe(1);

    $helloWorldText = dom\queryByText($dom, "/hello\s+world/i");
    expect($helloWorldText)->toBeInTheDocument();
    expect($helloWorldText)->toHaveClass('greeting');

    $holaText = dom\queryByText($dom, "/hola mundo/i");
    expect($holaText)->not()->toBeInTheDocument();

});

test("select", function() use($dom) {

    $select = dom\getByRole($dom, "menu");
    expect($select)->toBeInTheDocument();
    expect($select)->toHaveValue("2");
});