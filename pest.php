<?php 

namespace pest;

require_once(__DIR__."/src/expectation.php");
require_once(__DIR__."/src/mockfn.php");

/**
 * Assert the value of something by expecting the value being something.
 * @param value The value to test
 * @example expect(5 + 5)->toBe(10);
 * @return Expectation An expectation for the provided value. Use one of the matcher 
 * methods to assert the value.
 */
function expect($value)
{
    return new Expectation($value);
}

/**
 * Run a named test 
 * @param name The name of the test
 * @param callable The implementation of the test as a callable
 * @example test("add two numbers", function() {
 *      expect(1 + 2)->toBe(3);
 *      expect(0.2 + 0.1)->toBeCloseTo(0.3, 5); 
 * });
 */
function test($name, callable $callable) 
{
    try {
        if(is_callable($callable)) {
            $callable();
        } else {
            throw new \Exception("test(): callable is not a function");
        }
        echo "\033[92m o PASS \033[0m";
        echo $name, PHP_EOL;
    } catch (\Exception $e) {
        echo "\033[91m x FAIL \033[0m";
        echo $name, PHP_EOL, "\t", $e->getMessage(), PHP_EOL;
    }
}

/**
 * Create a mocked function by providing a mock implementation as a callable.
 * @param callable The mock implementation as a callable.
 * @return MockFn The mock function
 */
function mockfn(callable $callable)
{
    $mock = new MockFn();
    $mock->mockImplementation($callable);
    return $mock;
}