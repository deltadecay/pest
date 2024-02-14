<?php

namespace DOMTests;

require_once(__DIR__."/../pest.php");
require_once(__DIR__."/../src/dom/dom.php");


use function \pest\test;
use function \pest\expect;
use \pest\dom;



test("cssSelectorToXPath", function() {

    $q = \pest\dom\cssSelectorToXPath(".red.blue");
    expect($q)->toMatch("//*[contains(concat(' ',normalize-space(@class),' '),' red ')][contains(concat(' ',normalize-space(@class),' '),' blue ')]");

    $q = \pest\dom\cssSelectorToXPath("#my-id");
    expect($q)->toMatch("//*[@id='my-id']");

    $q = \pest\dom\cssSelectorToXPath("a#my-link");
    expect($q)->toMatch("//a[@id='my-link']");

    $q = \pest\dom\cssSelectorToXPath("a#my-link.important");
    expect($q)->toMatch("//a[@id='my-link'][contains(concat(' ',normalize-space(@class),' '),' important ')]");

    $q = \pest\dom\cssSelectorToXPath("input");
    expect($q)->toMatch("//input");

    $q = \pest\dom\cssSelectorToXPath("script, style");
    expect($q)->toMatch("//script|//style");

    $q = \pest\dom\cssSelectorToXPath("div.button");
    expect($q)->toMatch("//div[contains(concat(' ',normalize-space(@class),' '),' button ')]");

    $q = \pest\dom\cssSelectorToXPath("ul.menu li.item");
    expect($q)->toMatch("//ul[contains(concat(' ',normalize-space(@class),' '),' menu ')]//li[contains(concat(' ',normalize-space(@class),' '),' item ')]");
    
    $q = \pest\dom\cssSelectorToXPath("div > span.msg");
    expect($q)->toMatch("//div/span[contains(concat(' ',normalize-space(@class),' '),' msg ')]");
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
