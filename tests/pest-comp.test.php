<?php 
namespace PestComponentTests;

require_once(__DIR__."/../pest.php");
require_once(__DIR__."/../pest-dom.php");
require_once(__DIR__."/../pest-comp.php");


use function \pest\test;
// Make sure to use the dom specific expect
use function \pest\dom\expect;

use function \pest\comp\makeRef;
use function \pest\comp\render;
use function \pest\comp\debug;


function Thumbnail($props)
{
    $avatar_ref = $props["avatar"];
    $avatar = $avatar_ref->current;
 
    $title = htmlspecialchars($avatar["name"]);
    $str = <<<HTML
    <div>
      <img src="$avatar[image]" alt="$title" title="$title" />
    </div>
HTML;
    return $str;
}

function ContactButton($props)
{
    $avatar_ref = $props["avatar"];
    $avatar = $avatar_ref->current;
 
    $str = <<<HTML
    <button onclick="popupContactDialog('$avatar[id]')">Contact</button>
HTML;
    return $str;
}


function Avatar($props) 
{
    $avatar_ref = $props["avatar"];
    $avatar = $avatar_ref->current;

    $str = <<<HTML
    <div>
      <Thumbnail avatar={$avatar_ref} />
      <a href="$avatar[url]">
        <h3>$avatar[name]</h3>
        <p>$avatar[description]</p>
      </a>
      <ContactButton avatar={$avatar_ref} />
    </div>
HTML;
    return $str;
};


function getAvatarData()
{
    $avatar = [
        "id" => "a4185ae3-d757-429b-97fe-6fffdab5d037", 
        "url" => "https://gravatar.com/deba31bcb20d91f4cb343c8cda1337bdb9ed53ed8aa0cf79a9a2e37956748181",
        "image" => "https://gravatar.com/avatar/deba31bcb20d91f4cb343c8cda1337bdb9ed53ed8aa0cf79a9a2e37956748181?s=300&d=robohash",
        "name" => "Tin Rust-Bot",
        "description" => "Alien robot in space. Beeeep! Boooop!",
    ];
    return $avatar;
}

function AvatarApp()
{
    $avatar = getAvatarData();
    $avatar_ref = makeRef($avatar);
    return "<Avatar avatar=\"$avatar_ref\" />";
}

test("Avatar component renders", function() {

    $comp = render("<AvatarApp />", __NAMESPACE__);
    debug();

    expect($comp)->toHaveTextContent("/Alien robot in space/");

    $heading = \pest\dom\queryByRole($comp, "heading", ["name" => "/Tin Rust-Bot/"]);
    //expect($heading)->toHaveTextContent("/Tin Rust-Bot/");
    expect($heading)->toBeInTheDocument();

    // Testing implementation, not recommended, but can be done 
    $thelink = \pest\dom\querySelector($comp, "div > a");
    expect($thelink->getAttribute("href"))->toBe("https://gravatar.com/deba31bcb20d91f4cb343c8cda1337bdb9ed53ed8aa0cf79a9a2e37956748181");

    $button = \pest\dom\queryByRole($comp, "button", ["name" => "Contact"]);
    expect($button)->toBeInTheDocument();

    $img = \pest\dom\queryByRole($comp, "img", ["name" => "/Tin Rust-Bot/"]);
    expect($img)->toBeInTheDocument();
});