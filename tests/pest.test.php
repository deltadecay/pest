<?php 

namespace PestTests;

require_once(__DIR__."/../pest.php");

use \Exception;

use function \pest\test;
use function \pest\expect;
use function pest\beforeEach;
use function pest\afterEach;
use function \pest\mockfn;

test("equality", function() {
    $a = ["name" => "Bob", "age" => 34];
    $b = ["name" => "Bob", "age" => 34];
    $c = ["name" => "Bob", "age" => 34, "country" => "US"];

    expect($a)->toBe($b);
    expect($a)->not()->toBe($c);

    expect(5)->toBe("5");
    expect(5)->not()->toBeEqual("5");

    expect("01")->toBe(1);
    expect("1e2")->toBe(100);
    expect("1e2")->not()->toBeEqual(100);
    
    if (PHP_VERSION_ID < 80000) {
        // This is equal (==) up to php 7, but not in php 8
        expect("a")->toBe(0);
    } else {
        expect("a")->not()->toBe(0);
    }
});

test("add two ints", function() {
    expect(5 + 6)->toBeEqual(11);
    expect(5 + 6)->toBeGreaterThanOrEqual(10);
    expect(5 + 6)->toBeLessThan(15);
    expect(5 + 6)->toBeLessThanOrEqual(11);
});

test("null", function() {
    $n = null;
    expect($n)->toBeNull();
    expect($n)->not()->toBeSet();
    expect($n)->toBeUnset();
    expect($n)->not()->toBeTruthy();
    expect($n)->toBeFalsy();
});
  
test("zero", function() {
    $z = 0;
    expect($z)->toBeEqual(0);
    expect($z)->not()->toBeNull();
    expect($z)->toBeSet();
    expect($z)->not()->toBeUnset();
    expect($z)->not()->toBeTruthy();
    expect($z)->toBeFalsy();
});

test("testing floats may not always be ==", function() {
    expect(0.2 + 0.1)->not()->toBe(0.3); 
});
test("testing floats with tolerance", function() {
    expect(0.2 + 0.1)->toBeCloseTo(0.3, 5);  
});

test("instanceof", function() {
    class A {}
    class B extends A {}
    $a = new A();
    $b = new B();
    expect($a)->toBeInstanceOf(get_class($a));  
    expect($b)->toBeInstanceOf(__NAMESPACE__."\\B");  
    expect($b)->toBeInstanceOf(__NAMESPACE__."\\A");  
    expect($a)->not()->toBeInstanceOf(__NAMESPACE__."\\B");  
});

test("match with regexp", function() {
    $a = "abcdef";
    expect($a)->toMatch("/^abc/");  
    expect($a)->toMatch("/def$/");  
    expect($a)->not()->toMatch("/^abc$/");  
});

test("match with empty string", function() {
    $a = "";
    expect("" == "")->toBeTruthy();
    expect("" === "")->toBeTruthy();
    expect($a)->toMatch("");   
    expect($a)->toMatch("/^$/");   
});

test("match with options", function() {
    $text = " This   is  a sentence.  ";
    expect($text)->not()->toMatch("This is a sentence.");   
    
    $opts = [
        // Turn off the default no-normalizer in toMatch
        //"normalizer" => false,
        // Setting these will imply to not use the no-normalizer
        "trimWhitespace" => true,
        "collapseWhitespace" => true,
    ];
    expect($text)->toMatch("This is a sentence.", $opts);  

    expect("Some text here to partially match.")->not()->toMatch("partially match");
    // The exact option only applies when match pattern is not a regexp, but a regular string.
    expect("Some text here to partially match.")->toMatch("partially match", ["exact" => false]);
});


test("throws", function() {

    function afunc() {
        throw new Exception("afunc throws a lot");
    };

    function bfunc() {
        return 1337;
    }

    expect(function() { afunc(); })->toThrow();  
    expect(function() { afunc(); })->toThrow("afunc throws a lot");  
    expect(function() { afunc(); })->toThrow("/afunc thro/");  
    expect(function() { afunc(); })->not()->toThrow(new Exception("afunc throws"));  
    expect(function() { afunc(); })->toThrow(new Exception("afunc throws a lot")); 

    expect(function() { bfunc(); })->not()->toThrow();  
});


  
test("the shopping list has milk on it", function() {

    $shoppingList = [
        'diapers',
        'kleenex',
        'trash bags',
        'paper towels',
        'milk',
    ];

    expect($shoppingList)->toContain('milk');
});


test("check persons in list", function() {
    $people = [
        ["name" => "Bob", "age" => 35],
        ["name" => "Peter", "age" => 40],
        ["name" => "Alice", "age" => 33],
    ];

    $peter = ["name" => "Peter", "age" => 40];
    $charlie = ["name" => "Charlie", "age" => 39];
    expect($people)->toContain($peter);
    expect($people)->not()->toContain($charlie);
    expect($people)->not()->toContain(["name" => "Bob"]);
    expect($peter)->toBeInArray($people);
    expect($charlie)->not()->toBeInArray($people);
});

test("arrays", function() {

    expect([1,2,4])->not()->toContain(3);
    expect([1,2,4])->toContain(2);
    expect([1,2,4])->toHaveCount(3);
    expect([1,2,4])->not()->toHaveCount(1);

    expect(2)->toBeInArray([1,2,4]);
    expect(3)->not()->toBeInArray([1,2,4]);
});

test("array has key", function() {
    $a = ["akey" => 3, "data" => "hello", "8" => 16, 1337 => -1, "nullvalue" => null];

    expect($a)->toHaveKey("akey");
    expect($a)->toHaveKey("data");
    expect($a)->toHaveKey("8");
    // Keys that can be parsed to ints are integer keys
    expect($a)->toHaveKey(8);
    expect($a)->toHaveKey(1337);
    expect($a)->toHaveKey("1337");

    expect($a)->not()->toHaveKey("xdata");

    // isset returns false if a value is null
    expect($a["nullvalue"])->not()->toBeSet();
    expect($a["nullvalue"])->toBeUnset();

    // but with toHaveKey we can test existence even if value null
    expect($a)->toHaveKey("nullvalue");    
});

test("object has property", function() {
    class C {
        public $data;
        private $hasData;
    };
    $c = new C();

    expect($c)->toHaveProperty('data');
    expect($c)->toHaveProperty('hasData');
    expect(__NAMESPACE__."\\C")->toHaveProperty('data');
    expect($c)->not()->toHaveProperty('xy');

    $b = new \stdClass;
    $b->prop1 = 5;
    $b->prop2 = "abc";
    expect($b)->toHaveProperty("prop1");
    expect($b)->toHaveProperty("prop2");
    expect($b)->not()->toHaveProperty("propx");
});


test("mock function", function() {

    $mockAdd = mockfn(function($a, $b) {
        if($a < 0) {
            throw new Exception("a cannot be negative");
        }
        return $a + $b;
    });

    $res1 = $mockAdd(1, 2);
    $res2 = $mockAdd(3, 4);

    expect($res1)->toBeEqual(3);
    expect($res2)->toBeEqual(7);

    expect($mockAdd)->toHaveBeenCalled();
    expect($mockAdd)->toHaveBeenCalledTimes(2);

    expect($mockAdd)->toHaveReturned();
    expect($mockAdd)->toHaveReturnedTimes(2);
    

    expect($mockAdd->getResults()[0]['value'])->toBeEqual(3);
    expect($mockAdd)->toHaveNthReturnedWith(0, 3);
    expect($mockAdd->getCalls()[0])->toBe([1,2]);
    expect($mockAdd)->toHaveBeenNthCalledWith(0, 1, 2);

    expect($mockAdd->getResults()[1]['value'])->toBeEqual(7);
    expect($mockAdd)->toHaveNthReturnedWith(1, 7);
    expect($mockAdd->getCalls()[1])->toBe([3,4]);
    expect($mockAdd)->toHaveBeenNthCalledWith(1, 3, 4);

    // Clear the previous calls but keep the implementation
    $mockAdd->mockClear();
    expect($mockAdd)->not()->toHaveBeenCalled();
    expect($mockAdd)->toHaveBeenCalledTimes(0);
    $res3 = $mockAdd(10, 11);
    $res4 = $mockAdd(-11, 11); // This throws
    expect($res3)->toBeEqual(21);
    expect($mockAdd)->toHaveBeenCalledTimes(2);
    expect($mockAdd)->toHaveReturned();
    expect($mockAdd)->toHaveReturnedTimes(1);

    expect($mockAdd->getResults()[0]['value'])->toBeEqual(21);
    expect($mockAdd)->toHaveNthReturnedWith(0, 21);
    expect($mockAdd->getCalls()[0])->toBe([10,11]);
    expect($mockAdd)->toHaveBeenNthCalledWith(0, 10, 11);
    expect($mockAdd->getResults()[1]['type'])->toBe("throw");
    expect($mockAdd)->not()->toHaveNthReturnedWith(1, 0);
    expect($mockAdd)->toHaveBeenNthCalledWith(1, -11, 11);

    // Reset the mock implementation to a empty function() {};
    $mockAdd->mockReset();
    expect($mockAdd)->not()->toHaveBeenCalled();
    expect($mockAdd)->toHaveBeenCalledTimes(0);

    $mockAdd();
    expect($mockAdd)->toHaveBeenCalledTimes(1);
    expect($mockAdd)->toHaveReturnedTimes(1);
    expect($mockAdd->getResults()[0]['value'])->toBeUnset();
    expect($mockAdd->getResults()[0]['value'])->toBeNull();
    expect($mockAdd)->toHaveNthReturnedWith(0, null);
    expect($mockAdd->getCalls()[0])->toBe([]);
    expect($mockAdd)->toHaveBeenNthCalledWith(0);
});


test("nested tests", function() {

    beforeEach(function($name) { 
        //echo "Before $name\n";
    });
    afterEach(function($name) { 
        //echo "After $name\n";
    });

    test("test 1 nested", function() {
        expect(1)->toBe(1);
    });
    test("test 2 nested", function() {
        test("test 2.1 nested", function() {
            expect(2)->toBe(2);
        });
    });
});

