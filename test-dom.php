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
</div>
HTML;

$dom = dom\parse($src);


test("query ByRole", function() use($dom) {
    //dom\debug($dom);

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

test("select", function() {

    $src = <<<HTML
    <select name="choices" role="menu">
        <option value="1">First</option> 
        <option value="2" selected>Second</option> 
        <option value="3">Third</option> 
    </select>
HTML;
    $dom = dom\parse($src);

    $select = dom\getByRole($dom, "menu");
    expect($select)->toBeInTheDocument();
    expect($select)->toHaveValue("2");
});

test("select multiple", function() {

    $src = <<<HTML
    <label for="dino-select">Choose a dinosaur:</label>
    <select id="dino-select" role="menu" multiple>
        <optgroup label="Theropods">
            <option selected>Tyrannosaurus</option>
            <option>Velociraptor</option>
            <option>Deinonychus</option>
        </optgroup>
        <optgroup label="Sauropods">
            <option>Diplodocus</option>
            <option selected>Saltasaurus</option>
            <option>Apatosaurus</option>
        </optgroup>
    </select>
HTML;

    $dom = dom\parse($src);
    //dom\debug($dom);

    $select = dom\getByRole($dom, "menu");
    expect($select)->toBeInTheDocument();
    expect($select)->toHaveValue(["Tyrannosaurus", "Saltasaurus"]);
});



test("query ByTestId", function() {

    $src = <<<HTML
    <div data-testid="my-custom-greeting">
        <span>Hello</span>
    </div>
HTML;
    $dom = dom\parse($src);

    $select = dom\getByTestId($dom, "my-custom-greeting");
    expect($select)->toBeInTheDocument();
});


test("query ByTitle", function() {

    $src = <<<HTML
    <div>
        <span title="Delete" id="2"></span>
        <svg>
        <title>Close</title>
        <g><path /></g>
        </svg>
    </div>
HTML;
    $dom = dom\parse($src);
    //dom\debug($dom);

    $deleteElement = dom\getByTitle($dom, "Delete");
    expect($deleteElement)->toBeInTheDocument();
    $closeElement = dom\getByTitle($dom, "Close");
    expect($closeElement)->toBeInTheDocument();
});


test("query ByAltText", function() {

    $src = <<<HTML
    <div>
        <img alt="Incredibles 2 Poster" src="/incredibles-2.png" />
    </div>
HTML;
    $dom = dom\parse($src);
    //dom\debug($dom);

    $incrediblesPosterImg = dom\getByAltText($dom, "/incredibles.*? poster/i");
    expect($incrediblesPosterImg)->toBeInTheDocument();
});



