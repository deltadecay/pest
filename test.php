<?php 

require_once(__DIR__."/pest.php");



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
    expect($b)->toBeInstanceOf('B');  
    expect($b)->toBeInstanceOf('A');  
    expect($a)->not()->toBeInstanceOf('B');  
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


