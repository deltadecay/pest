<?php

namespace pest\utils;

function hasTextMatch($pattern, $str) {
    if ($str == null) {
        return false;
    }
    // Check if pattern is a regexp
    if (@preg_match($pattern, '') === false){
        // not a regexp 
        $hasMatch = $pattern == $str;
    } else {
        // a valid regexp
        $hasMatch = preg_match($pattern, $str);
    }
    return $hasMatch;
}


function computeAccessibleName(\DOMNode $node) {

    // TODO See https://www.w3.org/TR/accname-1.1/#mapping_additional_nd
    return $node->textContent;
}