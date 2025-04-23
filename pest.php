<?php 

namespace pest;

require_once(__DIR__."/src/expectation.php");
require_once(__DIR__."/src/testcontext.php");
require_once(__DIR__."/src/mockfn.php");

/**
 * Assert the value of something by expecting the value being something.
 * @param mixed The value to test
 * @example expect(5 + 5)->toBe(10);
 * @return Expectation An expectation for the provided value. Use one of the matcher 
 * methods to assert the value.
 */
function expect($value)
{
    return new Expectation($value);
}


function beforeEach(callable $callable)
{
    $ctx = getCurrentTestContext();
    if(is_callable($callable)) {
        $ctx->beforeEachTestFunc = $callable;
    } else {
        throw new \Exception("beforeEach(): callable is not a function");
    }
}

function afterEach(callable $callable)
{
    $ctx = getCurrentTestContext();
    if(is_callable($callable)) {
        $ctx->afterEachTestFunc = $callable;
    } else {
        throw new \Exception("afterEach(): callable is not a function");
    }
}

/**
 * Run a named test 
 * @param string The name of the test
 * @param callable The implementation of the test as a callable
 * @example test("add two numbers", function() {
 *      expect(1 + 2)->toBe(3);
 *      expect(0.2 + 0.1)->toBeCloseTo(0.3, 5); 
 * });
 */
function test($name, callable $callable) 
{
    $ctx = getCurrentTestContext();
    $tabs = str_repeat("\t", max(0, $ctx->depth));

    if(is_callable($ctx->beforeEachTestFunc)) {
        ($ctx->beforeEachTestFunc)($name);
    }

    $ex = null;
    try {
        pushTestContext($name);
        ob_start();
        if(is_callable($callable)) {
            $callable();
        } else {
            throw new \Exception("test(): callable is not a function");
        }

        $nestedCtx = getCurrentTestContext();
        if($nestedCtx->numTestsFailed > 0) {
            throw new \Exception($nestedCtx->numTestsFailed." nested test(s) failed");
        }

    } catch (\Exception $e) {
        $ex = $e;
    } finally {
        $nestedOutput = ob_get_clean();
        popTestContext();
    }

    $statusCode = "\033[92m o PASS \033[0m";
    $errMsg = "";
    if($ex instanceof \Exception) {
        $ctx->numTestsFailed++;
        $statusCode = "\033[91m x FAIL \033[0m";
        $errMsg = PHP_EOL.$tabs."\t".$ex->getMessage();
    } else {
        $ctx->numTestsSucceeded++;
    }
    echo $tabs.$statusCode;
    echo $name.$errMsg.PHP_EOL;
    echo $nestedOutput;

    if(is_callable($ctx->afterEachTestFunc)) {
        ($ctx->afterEachTestFunc)($name);
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