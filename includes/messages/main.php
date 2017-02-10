<?php
return [
	"empty" => [
		"text"	=> 'This field has to be filled out: %s',
		"additions" => 1,
		"error" => 1
	],
	"shorttagline" => [
    "text" => "The tagline text was too short!",
		"error" => 1
	],
	"shorttitle" => [
    "text" => "The title was too short, make it informative!",
		"error" => 1
	],
	"no_categories" => [
		"text" => "You have to give the article at least one category tag!",
		"error" => 1
	],
	"taglinetoolong" => [
		"text" => "The tagline was too long, it needs to be %d characters or less!",
		"additions" => 1,
		"error" => 1
	],
	"editor_picks_full" => [
		"text" => "There are already enough editor picks, the max is %d!",
		"additions" => 1,
		"error" => 1
	],
	"noimageselected" => [
		"text" => "You didn't select a tagline image to upload with the article, all articles must have one!",
		"error" => 1
	],
	"no_id" => [
		"text" => "There was no %s ID number given!",
		"additions" => 1,
		"error" => 1
	],
	"already_approved" => [
		"text" => "That has already been approved, someone must have gotten there first!",
		"error" => 1
	],
	"accepted" => [
		"text" => "That %s has now been accepted.",
		"additions" => 1,
		"error" => 0
	],
	"article_in_review" => [
		"text" => "Your article has been sent to the review queue for other editors to take a look.",
		"error" => 0
	],
	"deleted" => [
		"text" => "That %s has now been deleted.",
		"additions" => 1,
		"error" => 0
	],
	"saved" => [
		"text" => "That %s has now been saved.",
		"additions" => 1,
		"error" => 0
	],
	"edited" => [
		"text" => "That %s has now been edited.",
		"additions" => 1,
		"error" => 0
	],
	"banned" => [
		"text" => "That user is now banned!",
		"error" => 1
	],
	"unbanned" => [
		"text" => "That user is now unbanned!",
		"error" => 0
	],
	"password-match" => [
		"text" => "Your password did not match what we have for you!",
		"error" => 1
	],
	"not-that-email" => [
		"text" => "You cannot use that email address, as it's in use!",
		"error" => 1
	],
	"none_found" => [
		"text" => "No %s where found!",
		"additions" => 1
	],
	"notloggedin" => [
		"text" => "You have to be logged in to do that!",
		"error" => 1
	],
	"game_submit_exists" => [
		"text" => "That game already exists! You can find it <a href=\"/index.php?module=game&game-id=%d\">by clicking here.</a>",
		"additions" => 1,
		"error" => 1
	],
	"game_submitted" => [
		"text" => "You have sent in a game for our database! Thank you for helping to keep us up to date!"
	],
	"reported" => [
		"text" => "That %s was reported to the editors to review!",
		"additions" => 1,
		"error" => 0
	]
];
?>
