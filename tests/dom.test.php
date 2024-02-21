<?php

namespace DOMTests;

require_once(__DIR__."/../pest.php");
require_once(__DIR__."/../src/dom/dom.php");


use function \pest\test;
use function \pest\expect;
use \pest\dom;


test("parse", function() {
    $src = <<<HTML
    <div id="helloworld">Hello world!</div>
HTML;
    $dom = dom\parse($src);
    expect($dom instanceof \DOMDocument)->toBeTruthy();
    $hello = $dom->getElementById("helloworld");
    expect($hello)->not()->toBeNull();
    expect($hello->textContent)->toMatch("Hello world!");

    expect(\pest\dom\getDocument($hello))->toBe($dom);
    expect(\pest\dom\getDocument($dom))->toBe($dom);

    $p = new \DOMElement("p");
    expect(function() use($p) { \pest\dom\getDocument($p); })->toThrow("/No owner document/i");
});


test("expectAtMostOne", function() {
    expect(\pest\dom\expectAtMostOne([]))->toBeNull();
    expect(\pest\dom\expectAtMostOne(["one"]))->toBeEqual("one");
    expect(function() { \pest\dom\expectAtMostOne(["one", "two"]); })->toThrow();
});

test("expectAtleastOne", function() {
    expect(function() { \pest\dom\expectAtleastOne([]); })->toThrow();
    expect(\pest\dom\expectAtleastOne(["one"]))->toBe(["one"]);
    expect(\pest\dom\expectAtleastOne(["one","two"]))->toBe(["one","two"]);
});

test("expectOnlyOne", function() {
    expect(function() { \pest\dom\expectOnlyOne([]); })->toThrow();
    expect(\pest\dom\expectOnlyOne(["one"]))->toBe("one");
    expect(function() { \pest\dom\expectOnlyOne(["one","two"]); })->toThrow();
});

test("readToken", function() {
    expect(\pest\dom\readToken(0, "div.p", "."))->toBe("div");
    expect(\pest\dom\readToken(0, "div>p", "."))->toBe("div>p");
    expect(\pest\dom\readToken(0, "div>p", ".>"))->toBe("div");
    expect(\pest\dom\readToken(4, "div>p.red", ".>"))->toBe("p");
    expect(\pest\dom\readToken(6, "div>p.red", ".>"))->toBe("red");
});


test("selectNthFromExpression", function() {
    expect(\pest\dom\selectNthFromExpression("odd", "position()"))->toBe("[(position() mod 2)=1]");
    expect(\pest\dom\selectNthFromExpression("even", "position()"))->toBe("[(position() mod 2)=0]");
    expect(\pest\dom\selectNthFromExpression("4", "position()"))->toBe("[4]");
    expect(\pest\dom\selectNthFromExpression("3n+3", "position()"))->toBe("[position()>=3 and ((position()-3) mod 3)=0]");
    expect(\pest\dom\selectNthFromExpression("3n-3", "position()"))->toBe("[((position()+3) mod 3)=0]");
    expect(\pest\dom\selectNthFromExpression("-n+3", "position()"))->toBe("[position()<=3 and ((position()-3) mod -1)=0]");
    expect(\pest\dom\selectNthFromExpression("0n + 2", "position()"))->toBe("[2]");
    expect(\pest\dom\selectNthFromExpression("-2n -4", "position()"))->toBe("[false]");
    expect(\pest\dom\selectNthFromExpression("asdasdasd", "position()"))->toBe("");
    expect(\pest\dom\selectNthFromExpression("", "position()"))->toBe("");

});


test("cssSelectorToXPath", function() {

    $q = \pest\dom\cssSelectorToXPath(".red.blue");
    expect($q)->toMatch(".//*[contains(concat(\" \",normalize-space(@class),\" \"),\" red \")][contains(concat(\" \",normalize-space(@class),\" \"),\" blue \")]");

    $q = \pest\dom\cssSelectorToXPath("#my-id");
    expect($q)->toMatch(".//*[@id=\"my-id\"]");

    $q = \pest\dom\cssSelectorToXPath("a#my-link");
    expect($q)->toMatch(".//a[@id=\"my-link\"]");

    $q = \pest\dom\cssSelectorToXPath("a#my-link.important");
    expect($q)->toMatch(".//a[@id=\"my-link\"][contains(concat(\" \",normalize-space(@class),\" \"),\" important \")]");

    $q = \pest\dom\cssSelectorToXPath("input");
    expect($q)->toMatch(".//input");

    $q = \pest\dom\cssSelectorToXPath("script, style,code , pre");
    expect($q)->toMatch(".//script|.//style|.//code|.//pre");

    $q = \pest\dom\cssSelectorToXPath("div.button");
    expect($q)->toMatch(".//div[contains(concat(\" \",normalize-space(@class),\" \"),\" button \")]");

    $q = \pest\dom\cssSelectorToXPath("ul.menu li.item");
    expect($q)->toMatch(".//ul[contains(concat(\" \",normalize-space(@class),\" \"),\" menu \")]//li[contains(concat(\" \",normalize-space(@class),\" \"),\" item \")]");
    
    $q = \pest\dom\cssSelectorToXPath("div > span.msg");
    expect($q)->toMatch(".//div/span[contains(concat(\" \",normalize-space(@class),\" \"),\" msg \")]");

    $q = \pest\dom\cssSelectorToXPath("a[title]");
    expect($q)->toMatch(".//a[@title]");

    $q = \pest\dom\cssSelectorToXPath("a[title=\"hello\"]");
    expect($q)->toMatch(".//a[@title=\"hello\"]");
    
    $q = \pest\dom\cssSelectorToXPath("a[title *= \"hello\"]");
    expect($q)->toMatch(".//a[contains(@title,\"hello\")]");

    $q = \pest\dom\cssSelectorToXPath("a[title^=\"hello\"]");
    expect($q)->toMatch(".//a[starts-with(@title,\"hello\")]");

    $q = \pest\dom\cssSelectorToXPath("a[title|=\"hello\"]");
    expect($q)->toMatch(".//a[@title=\"hello\" or starts-with(@title,\"hello-\")]");

    $q = \pest\dom\cssSelectorToXPath("a[title$= \"hello\"]");
    expect($q)->toMatch(".//a[substring(@title,string-length(@title)-(string-length(\"hello\")-1))=\"hello\"]");

    $q = \pest\dom\cssSelectorToXPath("a[title ~=\"hello\"]");
    expect($q)->toMatch(".//a[contains(concat(\" \",normalize-space(@title),\" \"),\" hello \")]");

    $q = \pest\dom\cssSelectorToXPath("a[href ^= \"https://\"][href$=\".org\"]");
    expect($q)->toMatch(".//a[starts-with(@href,\"https://\")][substring(@href,string-length(@href)-(string-length(\".org\")-1))=\".org\"]");

    $q = \pest\dom\cssSelectorToXPath("div span[title].blue");
    expect($q)->toMatch(".//div//span[@title][contains(concat(\" \",normalize-space(@class),\" \"),\" blue \")]");

    $q = \pest\dom\cssSelectorToXPath("*[title*=\"hell's\"]");
    expect($q)->toMatch(".//*[contains(@title,\"hell's\")]");

    $q = \pest\dom\cssSelectorToXPath("ul li:first-child");
    expect($q)->toMatch(".//ul//li[not(preceding-sibling::*)]");
   
    $q = \pest\dom\cssSelectorToXPath("ul li:last-child");
    expect($q)->toMatch(".//ul//li[not(following-sibling::*)]");

    $q = \pest\dom\cssSelectorToXPath("ul li:first-of-type");
    expect($q)->toMatch(".//ul//li[1]");

    $q = \pest\dom\cssSelectorToXPath("ul li:last-of-type");
    expect($q)->toMatch(".//ul//li[last()]");

    $q = \pest\dom\cssSelectorToXPath("ul li:nth-of-type(3)");
    expect($q)->toMatch(".//ul//li[3]");

    $q = \pest\dom\cssSelectorToXPath("ul li:nth-of-type(odd)");
    expect($q)->toMatch(".//ul//li[(position() mod 2)=1]");

    $q = \pest\dom\cssSelectorToXPath("ul li:nth-of-type(3n+2)");
    expect($q)->toMatch(".//ul//li[position()>=2 and ((position()-2) mod 3)=0]");

    $q = \pest\dom\cssSelectorToXPath("ul li:nth-of-type(4n-3)");
    expect($q)->toMatch(".//ul//li[((position()+3) mod 4)=0]");

    $q = \pest\dom\cssSelectorToXPath("ul li:nth-of-type(0n + 4)");
    expect($q)->toMatch(".//ul//li[4]");

    $q = \pest\dom\cssSelectorToXPath("ul li:nth-of-type(-n+3)");
    expect($q)->toMatch(".//ul//li[position()<=3 and ((position()-3) mod -1)=0]");

    $q = \pest\dom\cssSelectorToXPath("ul li:nth-of-type(-2n-3)");
    expect($q)->toMatch(".//ul//li[false]");

    $q = \pest\dom\cssSelectorToXPath("input:checked");
    expect($q)->toMatch(".//input[@selected or @checked]");

    $q = \pest\dom\cssSelectorToXPath("button:enabled");
    expect($q)->toMatch(".//button[@enabled]");

    $q = \pest\dom\cssSelectorToXPath("button:disabled");
    expect($q)->toMatch(".//button[@disabled]");

    $q = \pest\dom\cssSelectorToXPath("span:empty");
    expect($q)->toMatch(".//span[not(*) and not(normalize-space())]");
});


test("querySelector", function() {
    $src = <<<HTML
    <div id="helloworld">Hello <span class="red bold">world!</span></div>
    <span>Another <span class="bold">span</span></span>
    <button title="Hell's warm" enabled>X</button>
    <ul>
        <li>First</li>
        <li>Second</li>
        <li>Third</li>
    </ul>
    <br />
    <p>  </p>
HTML;
    $dom = dom\parse($src);
    expect(\pest\dom\querySelectorAll($dom, "div"))->toHaveCount(1);
    expect(\pest\dom\querySelectorAll($dom, "span"))->toHaveCount(3);
    expect(\pest\dom\querySelector($dom, "#helloworld")->tagName)->toBe("div");
    expect(\pest\dom\querySelector($dom, "#helloworld")->textContent)->toBe("Hello world!");
    expect(\pest\dom\querySelector($dom, "div .red")->textContent)->toBe("world!");
    expect(\pest\dom\querySelector($dom, "div .bold")->textContent)->toBe("world!");
    expect(\pest\dom\querySelectorAll($dom, ".bold"))->toHaveCount(2);
    expect(\pest\dom\querySelector($dom, "span span")->textContent)->toBe("span");
    expect(\pest\dom\querySelector($dom, "button[title*=\"Hell's\"]")->textContent)->toBe("X");
    expect(\pest\dom\querySelector($dom, "ul li:first-child")->textContent)->toBe("First");
    expect(\pest\dom\querySelector($dom, "ul li:last-child")->textContent)->toBe("Third");
    expect(\pest\dom\querySelector($dom, "ul li:first-of-type")->textContent)->toBe("First");
    expect(\pest\dom\querySelector($dom, "ul li:last-of-type")->textContent)->toBe("Third");
    expect(\pest\dom\querySelector($dom, "*:enabled")->textContent)->toBe("X");
    $empty = \pest\dom\querySelectorAll($dom, "*:empty");
    expect($empty)->toHaveCount(2);
    expect($empty[0]->tagName)->toBe("br");
    expect($empty[1]->tagName)->toBe("p");
});


test("querySelector nth-of-type", function() {
    $src = <<<HTML
    <ul>
        <li>First</li>
        <li>Second</li>
        <li>Third</li>
        <li>Fourth</li>
        <li>Fifth</li>
        <li>Sixth</li>
        <li>Seventh</li>
    </ul>
HTML;
    $dom = dom\parse($src);
    
    expect(\pest\dom\querySelector($dom, "ul li:nth-of-type( 3)")->textContent)->toBe("Third");
    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type( even)");
    expect($lis)->toHaveCount(3);
    expect($lis[0]->textContent)->toBe("Second");
    expect($lis[1]->textContent)->toBe("Fourth");
    expect($lis[2]->textContent)->toBe("Sixth");
    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(odd)");
    expect($lis)->toHaveCount(4);
    expect($lis[0]->textContent)->toBe("First");
    expect($lis[1]->textContent)->toBe("Third");
    expect($lis[2]->textContent)->toBe("Fifth");
    expect($lis[3]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(2n)");
    expect($lis)->toHaveCount(3);
    expect($lis[0]->textContent)->toBe("Second");
    expect($lis[1]->textContent)->toBe("Fourth");
    expect($lis[2]->textContent)->toBe("Sixth");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(2n + 1)");
    expect($lis)->toHaveCount(4);
    expect($lis[0]->textContent)->toBe("First");
    expect($lis[1]->textContent)->toBe("Third");
    expect($lis[2]->textContent)->toBe("Fifth");
    expect($lis[3]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(2n+3)");
    expect($lis)->toHaveCount(3);
    expect($lis[0]->textContent)->toBe("Third");
    expect($lis[1]->textContent)->toBe("Fifth");
    expect($lis[2]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(2n-3)");
    expect($lis)->toHaveCount(4);
    expect($lis[0]->textContent)->toBe("First");
    expect($lis[1]->textContent)->toBe("Third");
    expect($lis[2]->textContent)->toBe("Fifth");
    expect($lis[3]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(4n-1)");
    expect($lis)->toHaveCount(2);
    expect($lis[0]->textContent)->toBe("Third");
    expect($lis[1]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(n)");
    expect($lis)->toHaveCount(7);
    expect($lis[0]->textContent)->toBe("First");
    expect($lis[6]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(n - 3)");
    expect($lis)->toHaveCount(7);
    expect($lis[0]->textContent)->toBe("First");
    expect($lis[6]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(n + 2)");
    expect($lis)->toHaveCount(6);
    expect($lis[0]->textContent)->toBe("Second");
    expect($lis[5]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(n + 6)");
    expect($lis)->toHaveCount(2);
    expect($lis[0]->textContent)->toBe("Sixth");
    expect($lis[1]->textContent)->toBe("Seventh");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(-n + 3)");
    expect($lis)->toHaveCount(3);
    expect($lis[0]->textContent)->toBe("First");
    expect($lis[1]->textContent)->toBe("Second");
    expect($lis[2]->textContent)->toBe("Third");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(-2n + 4)");
    expect($lis)->toHaveCount(2);
    expect($lis[0]->textContent)->toBe("Second");
    expect($lis[1]->textContent)->toBe("Fourth");

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(-n)");
    expect($lis)->toHaveCount(0);

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(-2n-1)");
    expect($lis)->toHaveCount(0);

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(0n)");
    expect($lis)->toHaveCount(0);

    $lis = \pest\dom\querySelectorAll($dom, "li:nth-of-type(0)");
    expect($lis)->toHaveCount(0);

    expect(\pest\dom\querySelector($dom, "ul li:nth-of-type(0n + 4)")->textContent)->toBe("Fourth");
});

test("getBoolAttribute", function() {
    $src = <<<HTML
    <input name="active" type="checkbox" checked />
    <input name="uncredited" type="checkbox" />
HTML;
    $dom = dom\parse($src);
    $inputActive = \pest\dom\querySelector($dom, "input[name=\"active\"]");
    expect(\pest\dom\getBoolAttribute($inputActive, "checked"))->toBeTruthy();

    $inputUncredited = \pest\dom\querySelector($dom, "input[name=\"uncredited\"]");
    expect(\pest\dom\getBoolAttribute($inputUncredited, "checked"))->toBeFalsy();
});

test("isElementHidden", function() {
    $src = <<<HTML
    <div>Hello <span class="red bold" style="display: none;">world!</span></div>
    <button>Click</button>
HTML;
    $dom = dom\parse($src);
    $span = \pest\dom\querySelector($dom, "div span.red.bold");
    expect(\pest\dom\isElementHidden($span))->toBeTruthy();
    $button = \pest\dom\querySelector($dom, "button");
    expect(\pest\dom\isElementHidden($button))->toBeFalsy();
});

test("getInputValue", function() {
    $src = <<<HTML
    <input name="active" type="checkbox" checked />
    <input name="name" type="text" value="Peter Jackson" />
    <input name="age" type="number" value="55" />
HTML;
    $dom = dom\parse($src);
    $inputActive = \pest\dom\querySelector($dom, "input[name=\"active\"]");
    expect(\pest\dom\getInputValue($inputActive))->toBeEqual(true);

    $inputName = \pest\dom\querySelector($dom, "input[name=\"name\"]");
    expect(\pest\dom\getInputValue($inputName))->toBeEqual("Peter Jackson");

    $inputAge = \pest\dom\querySelector($dom, "input[name=\"age\"]");
    expect(\pest\dom\getInputValue($inputAge))->toBeEqual(55);
});


test("getSelectValue", function() {
    $src = <<<HTML
    <select name="choice">
        <option value="1">First</option>
        <option value="2" selected>First</option>
        <option value="3">First</option>
    </select>
    <select name="pets" multiple>
        <option value="cat_0">Cat</option>
        <option value="dog_1" selected>Dog</option>
        <option value="fish_2" selected>Fish</option>
        <option value="parrot_3">Parrot</option>
    </select>
HTML;
    $dom = dom\parse($src);

    $selectChoice = \pest\dom\querySelector($dom, "select[name=\"choice\"]");
    expect(\pest\dom\getSelectValue($selectChoice))->toBe("2");

    $selectPets = \pest\dom\querySelector($dom, "select[name=\"pets\"]");
    expect(\pest\dom\getSelectValue($selectPets))->toBe(["dog_1","fish_2"]);
    expect(\pest\dom\getSelectValue($selectPets, ["displayValue" => true]))->toBe(["Dog","Fish"]);
});

test("getElementValue", function() {
    $src = <<<HTML
    <input name="active" type="checkbox" checked />
    <input name="name" type="text" value="Peter Jackson" />
    <input name="age" type="number" value="55" />
    <select name="choice">
        <option value="1">First</option>
        <option value="2" selected>First</option>
        <option value="3">First</option>
    </select>
    <select name="pets" multiple>
        <option value="cat_0">Cat</option>
        <option value="dog_1" selected>Dog</option>
        <option value="fish_2" selected>Fish</option>
        <option value="parrot_3">Parrot</option>
    </select>
    <span>Some text here</span>
HTML;
    $dom = dom\parse($src);

    $inputActive = \pest\dom\querySelector($dom, "input[name=\"active\"]");
    expect(\pest\dom\getElementValue($inputActive))->toBeEqual(true);

    $inputName = \pest\dom\querySelector($dom, "input[name=\"name\"]");
    expect(\pest\dom\getElementValue($inputName))->toBeEqual("Peter Jackson");

    $inputAge = \pest\dom\querySelector($dom, "input[name=\"age\"]");
    expect(\pest\dom\getElementValue($inputAge))->toBeEqual(55);

    $selectChoice = \pest\dom\querySelector($dom, "select[name=\"choice\"]");
    expect(\pest\dom\getElementValue($selectChoice))->toBe("2");

    $selectPets = \pest\dom\querySelector($dom, "select[name=\"pets\"]");
    expect(\pest\dom\getElementValue($selectPets))->toBe(["dog_1","fish_2"]);
    expect(\pest\dom\getElementValue($selectPets, ["displayValue" => true]))->toBe(["Dog","Fish"]);

    $span = \pest\dom\querySelector($dom, "span");
    expect(\pest\dom\getElementValue($span))->toBeEqual("Some text here");
});


test("accessible names: read more", function() {
    $src = <<<HTML
<h2 id="bees-heading">7 ways you can help save the bees</h2>
<p>Bees are disappearing rapidly. Here are seven things you can do to help.</p>
<p><a id="bees-read-more" aria-labelledby="bees-read-more bees-heading">Read more...</a></p>
HTML;
    $dom = dom\parse($src);
    $para = \pest\dom\querySelector($dom, "#bees-read-more");
    $name = \pest\dom\computeAccessibleName($para);
    expect($name)->toMatch("Read more... 7 ways you can help save the bees");
});

test("accessible names: hidden", function() {
    $src = <<<HTML
<span id="night-mode-label" hidden>Night mode</span>
<input type="checkbox" role="switch" aria-labelledby="night-mode-label">
HTML;
    $dom = dom\parse($src);
    $input = \pest\dom\querySelector($dom, "input");
    $name = \pest\dom\computeAccessibleName($input);
    expect($name)->toMatch("Night mode");
});


test("accessible names: no name", function() {
    $src = <<<HTML
<input name="code">
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "input"));
    expect($name)->toMatch("");
});

test("accessible names: placeholder", function() {
    $src = <<<HTML
<input name="code"
  placeholder="One-time code">
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "input"));
    expect($name)->toMatch("One-time code");
});

test("accessible names: title", function() {
    $src = <<<HTML
<input name="code"
  placeholder="123456"
  title="One-time code">
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "input"));
    expect($name)->toMatch("One-time code");
});

test("accessible names: label implicit", function() {
    $src = <<<HTML
<label>One-time code
  <input name="code"
    placeholder="123456"
    title="Get your code from the app.">
</label>
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "input"));
    expect($name)->toMatch("One-time code");
});

test("accessible names: label explicit embedded", function() {
    $src = <<<HTML
<label for="code">One-time code
  <input id="code" name="code"
    placeholder="123456"
    title="Get your code from the app.">
</label>
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "input"));
    expect($name)->toMatch("One-time code");
});

test("accessible names: label explicit", function() {
    $src = <<<HTML
<label for="code">One-time code</label>
<input id="code" name="code"
    placeholder="123456"
    title="Get your code from the app.">

HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "input"));
    expect($name)->toMatch("One-time code");
});

test("accessible names: aria-label", function() {
    $src = <<<HTML
<label>Code
  <input name="code"
    aria-label="One-time code"
    placeholder="123456"
    title="Get your code from the app.">
</label>
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "input"));
    expect($name)->toMatch("One-time code");
});


test("accessible names: aria-labelledby", function() {
    $src = <<<HTML
<p>Please fill in your <span id="code-label">one-time code</span> to log in.</p>
<p>
  <label>Code
    <input name="code"
    aria-labelledby="code-label"
    aria-label="This is ignored"
    placeholder="123456"
    title="Get your code from the app.">
  </label>
</p>
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "input"));
    expect($name)->toMatch("one-time code");
});


test("accessible names: traverse button", function() {
    $src = <<<HTML
<button>Move to <img src="bin.svg" alt="trash"></button>
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "button"));
    expect($name)->toMatch("Move to trash");
});

test("accessible names: complex traversal", function() {
    $src = <<<HTML
<div id="meeting-1">
  <button aria-labelledby="meeting-1" aria-label="Remove meeting:">X</button>
  Daily status report
</div>
HTML;
    $dom = dom\parse($src);
    $name = \pest\dom\computeAccessibleName(\pest\dom\querySelector($dom, "button"));
    expect($name)->toMatch("Remove meeting: Daily status report");
});
