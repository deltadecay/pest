<?php

namespace pest\aria;

// Inherited aria roles to corresponding html elements
// List taken from => 
// https =>//www.npmjs.com/package/aria-query
function getRoleElementsMap() {
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
