<?php

namespace pest\utils;

function normalize($text, $options = []) 
{    
    $trim = isset($options['trimWhitespace']) ? $options['trimWhitespace'] : true;
    $collapseWhitespace = isset($options['collapseWhitespace']) ? $options['collapseWhitespace'] : true;

    $t = $text;
    if ($collapseWhitespace) {
        $t = preg_replace("/\s+/", " ", $t);
    }
    if ($trim) {
        $t = trim($t);
    }
    return $t;
}

function noNormalizer() 
{
    return function($str) { return $str; };
}

function getDefaultNormalizer($options = [])
{
    return function($str) use($options) { return normalize($str, $options); };
}

function hasTextMatch($pattern, $str, $options = []) 
{
    $exact = isset($options['exact']) ? $options['exact'] : true;
    $normalizer = isset($options['normalizer']) && is_callable($options['normalizer']) ? $options['normalizer'] : getDefaultNormalizer($options);

    if ($str === null) {
        return false;
    }

    // Normalize the input string
    $str = $normalizer($str);

    if($pattern == "") {
        // Special case when pattern is empty
        return $pattern == $str;
    }

    // Check if pattern is a regexp
    if (@preg_match($pattern, '') === false){
        // not a regexp 
        if($exact) {
            $hasMatch = $pattern == $str;
        } else {
            // Case-insensitive substring check
            $hasMatch = stristr($str, $pattern) !== false;
        }
    } else {
        // a valid regexp
        $hasMatch = preg_match($pattern, $str) === 1;
    }
    return $hasMatch;
}

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
  
function base64url_decode($data)
{
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}


