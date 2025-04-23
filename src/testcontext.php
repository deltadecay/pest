<?php

namespace pest;

class TestContext
{
    protected $name;
    protected $depth = 0;
    protected $beforeEachTestFunc = null;
    protected $afterEachTestFunc = null;
    protected $numTestsFailed = 0;


    public function __construct($name, $depth = 0)
    {
        $this->name = $name;
        $this->depth = $depth;
    }

    public function setBeforeEachCallback(callable $callback)
    {
        $this->beforeEachTestFunc = $callback;
    }

    public function setAfterEachCallback(callable $callback)
    {
        $this->afterEachTestFunc = $callback;
    }


    public function getNumTestsFailed() 
    {
        return $this->numTestsFailed;
    }


    public function increaseTestsFailed()
    {
        $this->numTestsFailed++;
    }

    public function beforeEachTest(...$params)
    {
        if(is_callable($this->beforeEachTestFunc)) {
            call_user_func_array($this->beforeEachTestFunc, $params);
        }
    }

    public function afterEachTest(...$params)
    {
        if(is_callable($this->afterEachTestFunc)) {
            call_user_func_array($this->afterEachTestFunc, $params);
        }
    }

    public function getTestStatusReport($name, $nestedOutput, $testException)
    {
        $tabs = str_repeat("\t", max(0, $this->depth));
        $statusCode = "\033[92m o PASS \033[0m";
        $errMsg = "";
        if($testException instanceof \Exception) {
            $statusCode = "\033[91m x FAIL \033[0m";
            $errMsg = PHP_EOL.$tabs."\t".$testException->getMessage();
        } 
        $str = $tabs.$statusCode;
        $str.= $name.$errMsg.PHP_EOL;
        $str.= $nestedOutput;
        return $str;
    }
}


// The stack of test contexts. Note! This is global.
$_testContextStack = [];

function getCurrentTestContext()
{
    global $_testContextStack;
    if(!is_array($_testContextStack)) {
        $_testContextStack = [];
    }
    $n = count($_testContextStack);
    if($n == 0)
    {
        // If no contexts, create the top most global context
        array_push($_testContextStack, new TestContext('global', $n));
        $n = 1;
    }
    // Current context is the last inserted into the stack
    return $_testContextStack[$n - 1];
}

function pushTestContext($name)
{
    global $_testContextStack;
    $n = count($_testContextStack);
    array_push($_testContextStack, new TestContext($name, $n));
}

function popTestContext()
{
    global $_testContextStack;
    array_pop($_testContextStack);
}

