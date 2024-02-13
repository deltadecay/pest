<?php

namespace pest\aria;

// Lists taken from:
// https://www.npmjs.com/package/aria-query

// Inherited aria roles to corresponding html elements
function getRoleElementsMap() 
{
    $roleElementsMap = [
        'article' => [ ["name" => "article"] ] ,
        'button' => [ ["name" => "button"] ] ,
        'cell' => [ ["name" => "td"] ] ,
        'checkbox' => [ ["name" => "input", "attributes" => [ ["name" => "type", "value" => "checkbox"]] ] ],
        'columnheader' => [ ["name" => "th"] ],
        'combobox' => [ ["name" => "select"] ],
        'command' => [ ["name" => "menuitem"] ],
        'definition' => [ ["name" => "dd"], ["name" => "dfn"] ],
        'figure' => [ ["name" => "figure"] ],
        'form' => [ ["name" => "form"] ],
        'grid' => [ ["name" => "table"] ],
        'gridcell' => [ ["name" => "td"] ],
        'group' => [ ["name" => "fieldset"] ],
        'heading' => [ ["name" => "h1"], ["name" => "h2"], ["name" => "h3"], ["name" => "h4"],  ["name" => "h5"], ["name" => "h6"] ],
        'img' => [ ["name" => "img"] ],
        'link' => [ ["name" => "a"], ["name" => "link"] ],
        'list' => [ ["name" => "ol"], ["name" => "ul"] ],
        'listbox' => [ ["name" => "select"] ],
        'listitem' => [ ["name" => "li"] ],
        'menuitem' => [ ["name" => "menuitem"] ],
        'navigation' => [ ["name" => "nav"] ],
        'option' => [ ["name" => "option"] ],
        'radio' => [ ["name" => "input", "attributes" => [ ["name" => "type", "value" => "radio"]] ] ],
        'region' => [ ["name" => "frame"] ],
        'roletype' => [ ["name" => "rel"] ],
        'row' => [ ["name" => "tr"] ],
        'rowgroup' => [ ["name" => "tbody"], ["name" => "tfoot"], ["name" => "thead"] ],
        'rowheader' => [ ["name" => "th", "attributes" => [ ["name" => "scope", "value" => "row"]] ], ["name" => "th", "attributes" => [ ["name" => "scope", "value" => "rowgroup"]] ] ],
        'searchbox' => [ ["name" => "input", "attributes" => [ ["name" => "type", "value" => "search"]] ] ],
        'separator' => [ ["name" => "hr"] ],
        'table' => [ ["name" => "table"] ],
        'term' => [ ["name" => "dt"] ],
        'textbox' => [ ["name" => "textarea"], ["name" => "input", "attributes" => [ ["name" => "type", "value" => "text"]] ] ],
    ];
    return $roleElementsMap;
}


// Elements to possible roles
function getElementRoleMap()
{
    $elementRoleMap = [
        "a" => [ [ "role" => 'link' ] ],
        "article" => [ [ "role" => 'article' ] ],
        "button" => [ [ "role" => 'button' ] ],
        "dd" => [ [ "role" => 'definition' ] ],
        "dfn" => [ [ "role" => 'term' ] ],
        "dt" => [ [ "role" => 'term' ] ],
        "fieldset" => [ [ "role" => 'group' ] ],
        "figure" => [ [ "role" => 'figure' ] ],
        "form" => [ [ "role" => 'form' ] ],
        "frame" => [ [ "role" => 'region' ] ],
        "h1" => [ [ "role" => 'heading' ] ],
        "h2" => [ [ "role" => 'heading' ] ],
        "h3" => [ [ "role" => 'heading' ] ],
        "h4" => [ [ "role" => 'heading' ] ],
        "h5" => [ [ "role" => 'heading' ] ],
        "h6" => [ [ "role" => 'heading' ] ],
        "hr" => [ [ "role" => 'separator' ] ],
        "img" => [ [ "role" => 'img' ] ],
        "input" => [
            [ "attribute" => ["name"=>"type", "value"=>"checkbox"], "role" => 'checkbox' ],
            [ "attribute" => ["name"=>"type", "value"=>"radio"], "role" => 'radio' ],
            [ "attribute" => ["name"=>"type", "value"=>"search"], "role" => 'searchbox' ],
            [ "attribute" => ["name"=>"type", "value"=>"text"], "role" => 'textbox' ],
        ],
        "li" => [ [ "role" => 'listitem' ] ],
        "link" => [ [ "role" => 'link' ] ],
        "menuitem" => [ 
            [ "role" => 'command' ],
            [ "role" => 'menuitem' ]
        ],
        "nav" => [ [ "role" => 'navigation' ] ],
        "ol" => [ [ "role" => 'list' ] ],
        "option" => [ [ "role" => 'option' ] ],
        "rel" => [ [ "role" => 'roletype' ] ],
        "select" => [ 
            [ "role" => 'combobox' ], 
            [ "role" => 'listbox' ]
        ],
        "table" => [ 
            [ "role" => 'grid' ], 
            [ "role" => 'table' ] 
        ],
        "tbody" => [ [ "role" => 'rowgroup' ] ],
        "td" => [ 
            [ "role" => 'cell' ],
            [ "role" => 'gridcell' ] 
        ],
        "textarea" => [ [ "role" => 'textbox' ] ],
        "tfoot" => [ [ "role" => 'rowgroup' ] ],
        "th" => [
            [ "attribute" => ["name"=>"scope", "value"=>"row"], "role" => 'rowheader' ],
            [ "role" => 'columnheader' ],
        ],
        "thead" => [ [ "role" => 'rowgroup' ] ],
        "tr" => [ [ "role" => 'row' ] ],
        "ul" => [ [ "role" => 'list' ] ],
    ];
    return $elementRoleMap;
}

// Check if a role supports computing accessible name from content (child nodes)
function isRoleSupportingNameFromContent($role)
{
    $roles = [
        "button" => 1,
        "cell" => 1,
        "checkbox" => 1,
        "columnheader" => 1,
        "gridcell" => 1,
        "heading" => 1,
        "link" => 1,
        "menuitem" => 1,
        "menuitemcheckbox" => 1,
        "menuitemradio" => 1,
        "option" => 1,
        "radio" => 1,
        "row" => 1,
        "rowheader" => 1,
        "sectionhead" => 1,
        "switch" => 1,
        "tab" => 1,
        "tooltip" => 1,
        "treeitem" => 1
    ];
    return $roles[$role] == 1;
}