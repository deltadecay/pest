<?php 
namespace PestDOMTests;

require_once(__DIR__."/../pest.php");
require_once(__DIR__."/../pest-dom.php");


use function \pest\test;
use \pest\dom;
// Make sure to use the dom specific expect
use function \pest\dom\expect;


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
    expect($checkbox)->toBeChecked();
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

test("query ByText ignore", function() use($dom) {
    

    $src = <<<HTML
    <script>var v = "Do not match this script";</script>
    <style>.match { color: #fff; }</style>
    <span class="match">Match only this span</span>
HTML;
    $dom = dom\parse($src);

    $matches = dom\queryAllByText($dom, "/match/i", ["ignore" => "script, style"]);
    expect(count($matches))->toBe(1);
    expect($matches[0])->toHaveTextContent("Match only this span");
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
    expect($select)->toHaveDisplayValue("Second");
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
    expect($select)->toHaveDisplayValue(["Tyrannosaurus", "Saltasaurus"]);
    // Since the options don't have value attribute, their values are the text content
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


test("query ByLabelText", function() {

    $src = <<<HTML
    <div>
        <label for="username-input">Username</label>
        <input id="username-input" />

        <label id="username-label">Username</label>
        <input aria-labelledby="username-label" />

        <label>Username <input /></label>

        <label>
            <span>Username</span>
            <input />
        </label>

        <input aria-label="Username" />
    </div>
HTML;
    $dom = dom\parse($src);
    //dom\debug($dom);

    $inputs = dom\queryAllByLabelText($dom, "Username");
    expect($inputs)->toHaveCount(5);

});


test("query ByPlaceholderText", function() {

    $src = <<<HTML
    <div>
        <input placeholder="Username" />

        <textarea placeholder="Biography, etc."></textarea>
    </div>
HTML;
    $dom = dom\parse($src);
    //dom\debug($dom);

    $inputs = dom\queryAllByPlaceholderText($dom, "Username");
    expect($inputs)->toHaveCount(1);
   
    $textarea = dom\getByPlaceholderText($dom, "/biography/i");
    expect($textarea)->toBeInTheDocument();
});


test("query ByDisplayValue", function() {

    $src = <<<HTML
    <div>
        <input type="text" id="lastName" value="Anderson" />
        <textarea id="messageTextArea">Some text message written here</textarea>

        <select>
            <option value="">State</option>
            <option value="AL">Alabama</option>
            <option selected value="AK">Alaska</option>
            <option value="AZ">Arizona</option>
        </select>
    </div>
HTML;
    $dom = dom\parse($src);
    //dom\debug($dom);

    $input = dom\getByDisplayValue($dom, "Anderson");
    expect($input)->toBeInTheDocument();
    expect($input)->toHaveValue("Anderson");

   
    $textarea = dom\getByDisplayValue($dom, "/Some text message/i");
    expect($textarea)->toBeInTheDocument();
    expect($textarea)->toHaveValue("Some text message written here");


    $select = dom\getByDisplayValue($dom, "Alaska");
    expect($select)->toBeInTheDocument();
    expect($select)->toHaveValue("AK"); // Note the value is AK, but display value Alaska
    expect($select)->toHaveDisplayValue("Alaska"); 
});

