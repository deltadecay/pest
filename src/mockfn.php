<?php

namespace pest;


class MockFn 
{
    private $callable = null;
    private $calls = [];
    private $results = [];


    public function __construct() 
    {
        $this->mockReset();
    }

    public function getCalls() 
    {
        return $this->calls;
    }
    public function getResults() 
    {
        return $this->results;
    }


    public function mockClear()
    {
        $this->calls = [];
        $this->results = [];
    }

    public function mockReset()
    {
        $this->mockClear();
        $this->callable = function() { };
    }

    public function mockImplementation(callable $callable) 
    {
        $this->callable = $callable;
    }

    public function __invoke(...$params)
    {
        $currentCallId = count($this->calls);

        $this->calls[$currentCallId] = $params;
        $this->results[$currentCallId] = ["type" => "incomplete"];

        $res = null;
        $result = ["type" => "return", "value" => $res];
        if(is_callable($this->callable)) {
            try {
                $res = call_user_func_array($this->callable, $params);
                $result["value"] = $res;
            } catch(\Exception $e) {
                $result = ["type" => "throw", "value" => $e];
            }
        }

        $this->results[$currentCallId] = $result;

        return $res;
    }
}
