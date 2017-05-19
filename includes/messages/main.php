<?php
return [
	"empty" => 
	[
		"text"	=> 'You left a required field empty. This field has to be filled out: <strong>%s</strong>',
		"additions" => 1,
		"error" => 1
	],
	"shorttagline" => 
	[
		"text" => "The tagline text was too short!",
		"error" => 1
	],
	"shorttitle" => 
	[
		"text" => "The post title was too short!",
		"error" => 1
	],
	"no_categories" => 
	[
		"text" => "You have to give the article at least one category tag!",
		"error" => 1
	],
	"editor_picks_full" => 
	[
		"text" => "There are already enough editor picks, the max is %d!",
		"additions" => 1,
		"error" => 1
	],
	"noimageselected" => 
	[
		"text" => "You didn't select a tagline image to upload with the article, all articles must have one!",
		"error" => 1
	],
	"no_id" => 
	[
		"text" => "There was no %s ID number given! This is likely a bug, please report it!",
		"additions" => 1,
		"error" => 1
	],
	"already_approved" => 
	[
		"text" => "That has already been approved, someone must have gotten there first!",
		"error" => 1
	],
	"accepted" => 
	[
		"text" => "That %s has now been accepted.",
		"additions" => 1,
		"error" => 0
	],
	"article_in_review" => 
	[
		"text" => "Your article has been sent to the review queue for other editors to take a look.",
		"error" => 0
	],
	"deleted" => 
	[
		"text" => "That %s has now been deleted.",
		"additions" => 1,
		"error" => 0
	],
	"saved" => 
	[
		"text" => "That %s has now been saved.",
		"additions" => 1,
		"error" => 0
	],
	"edited" => 
	[
		"text" => "That %s has now been edited.",
		"additions" => 1,
		"error" => 0
	],
	"password-match" => 
	[
		"text" => "Your password did not match what we have for you!",
		"error" => 1
	],
	"not-that-email" => 
	[
		"text" => "You cannot use that email address, as it's in use!",
		"error" => 1
	],
	"none_found" => 
	[
		"text" => "No %s where found!",
		"additions" => 1
	],
	"notloggedin" => 
	[
		"text" => "You have to be logged in to do that!",
		"error" => 1
	],
	"game_submit_exists" => 
	[
		"text" => "That game already exists! You can find it <a href=\"/index.php?module=game&game-id=%d\">by clicking here.</a>",
		"additions" => 1,
		"error" => 1
	],
	"reported" => 
	[
		"text" => "That %s was reported to the editors to review!",
		"additions" => 1,
		"error" => 0
	],
	"mod_queue" => 
	[
		"text" => "Your submission is now in the mod queue to be manually approved, please be patient while our editors work. You're either a new user who we need to make sure isn't a spammer, or you're on the naughty list.",
		"error" => 0
	],
	"one_link_needed" => [
		"text" => "At least one link is required!",
		"error" => 1
	],
	"locked" => [
		"text" => "Sorry, but that %s is currently locked!",
		"error" => 0,
		"additions" => 1
	],
	"no_permission" =>
	[
		"text" => "You do not have permission to do that!",
		"error" => 1
	],
	"captcha" =>
	[
		"text" => "You didn't do the captcha, which is necessary to combat spam!",
		"error" => 1
	],
	"banned" =>
	[
		"text" => "You were banned, possibly from spam detection or for breaking our rules!",
		"error" => 1
	],
	"new_account" =>
	[
		"text" => "Thank you for registering %s, you are now logged in, <strong>but you need to confirm your email before being able to post</strong>!",
		"additions" => 1
	]
];
?>
