<?php

namespace pest;

class TestContext
{
    public $name;
    public $depth = 0;
    public $beforeEachTestFunc = null;
    public $afterEachTestFunc = null;
    public $numTestsFailed = 0;
    public $numTestsSucceeded = 0;
    public function __construct($name, $depth = 0)
    {
        $this->name = $name;
        $this->depth = $depth;
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

