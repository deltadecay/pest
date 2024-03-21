<?php

namespace UtilsTests;

require_once(__DIR__."/../pest.php");


use function \pest\test;
use function \pest\expect;

test("normalize", function() {
    // Default is to both trim and collapse white space
    expect(\pest\utils\normalize("   "))->toBe("");
    expect(\pest\utils\normalize(" Hello   World!   "))->toBe("Hello World!");
    expect(\pest\utils\normalize(" Hello   World!   ", [ "trimWhitespace" => false]))->toBe(" Hello World! ");
    expect(\pest\utils\normalize(" Hello   World!   ", [ "trimWhitespace" => false, "collapseWhitespace" => false]))->toBe(" Hello   World!   ");
    expect(\pest\utils\normalize(" Hello   World!   ", [ "collapseWhitespace" => false]))->toBe("Hello   World!");

    expect(\pest\utils\normalize("   ", [ "trimWhitespace" => false]))->toBe(" ");

});


test("hasTextMatch empty pattern and empty string", function() {
    expect(\pest\utils\hasTextMatch("", "", ["normalizer" => \pest\utils\noNormalizer()]))->toBeTruthy();
});

test("hasTextMatch", function() {
    $text = "This is a sentence.";
    expect(\pest\utils\hasTextMatch("This is a sentence.", $text))->toBeTruthy();
    expect(\pest\utils\hasTextMatch("this is a sentence", $text))->toBeFalsy();
    expect(\pest\utils\hasTextMatch("this is a sentence", $text, ["exact" => true]))->toBeFalsy();
    expect(\pest\utils\hasTextMatch("this is a sentence", $text, ["exact" => false]))->toBeTruthy();
    expect(\pest\utils\hasTextMatch("sentence", $text, ["exact" => false]))->toBeTruthy();

    expect(\pest\utils\hasTextMatch("/this is a sentence/i", $text))->toBeTruthy();

    $re = "/this.+sentence/i";
    expect(\pest\utils\hasTextMatch($re, "This is a sentence."))->toBeTruthy();
    expect(\pest\utils\hasTextMatch($re, "This sentence."))->toBeTruthy();
    expect(\pest\utils\hasTextMatch($re, "Thissentence."))->toBeFalsy();

 

});

test("hasTextMatch normalize", function() {
   // Default is to normalize the input text
   $text = " This   is  a sentence.  ";
   expect(\pest\utils\hasTextMatch("This is a sentence.", $text))->toBeTruthy();
   expect(\pest\utils\hasTextMatch("this is a sentence", $text))->toBeFalsy();
   expect(\pest\utils\hasTextMatch("this is a sentence", $text, ["exact" => true]))->toBeFalsy();
   expect(\pest\utils\hasTextMatch("this is a sentence", $text, ["exact" => false]))->toBeTruthy();
   expect(\pest\utils\hasTextMatch("sentence", $text, ["exact" => false]))->toBeTruthy();
   expect(\pest\utils\hasTextMatch("/this is a sentence/i", $text))->toBeTruthy();

   // Turn off normalizer, no trim or white space collapse is done
   $nonormalizer = \pest\utils\noNormalizer();

   expect(\pest\utils\hasTextMatch("This is a sentence.", $text, ["normalizer" => $nonormalizer]))->toBeFalsy();

   // Customize the default normalizer, turn off trim and whitespace collapse
   $defnormalizer = \pest\utils\getDefaultNormalizer([ "trimWhitespace" => false, "collapseWhitespace" => false ]);
   expect(\pest\utils\hasTextMatch("This is a sentence.", $text, ["normalizer" => $defnormalizer]))->toBeFalsy();

   // An entire different normalizer, transform the text to lower case
   $lower = function($str) { return strtolower($str); };
   expect(\pest\utils\hasTextMatch("abc def", "ABC DEF", ["normalizer" => $lower]))->toBeTruthy();
   expect(\pest\utils\hasTextMatch("ABC DEF", "ABC DEF", ["normalizer" => $lower]))->toBeFalsy();

});


test("base64url_encode", function() {
    expect(\pest\utils\base64url_encode(""))->toBe("");
    expect(base64_encode("hello world"))->toBe("aGVsbG8gd29ybGQ=");
    expect(\pest\utils\base64url_encode("hello world"))->toBe("aGVsbG8gd29ybGQ");
});

test("base64url_decode", function() {
    expect(\pest\utils\base64url_decode(""))->toBe("");
    expect(base64_decode("aGVsbG8gd29ybGQ="))->toBe("hello world");
    expect(\pest\utils\base64url_decode("aGVsbG8gd29ybGQ"))->toBe("hello world");
});
