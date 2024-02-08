<?php 

namespace ExampleTests;

require_once(__DIR__."/pest.php");


use \Exception;

use function \pest\test;
use function \pest\expect;
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