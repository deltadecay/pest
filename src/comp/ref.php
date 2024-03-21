<?php

namespace pest\comp;

require_once(__DIR__."/../utils.php");


class Ref 
{
    public $current;
    private $id;
    public function __construct($id, $current)
    {
        $this->current = $current;
        $this->id = $id;
    }

    public function __toString()
    {
        $str = \pest\utils\base64url_encode(json_encode(["vrdomrefid" => $this->id]));
        return $str;
    }
}