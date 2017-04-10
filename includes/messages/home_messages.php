<?php
return [
	"unliked" => 
	[
		"text" => "You have unliked all articles and comments!"
	],
	"unsubscribed" =>
	[
		"text" => "You have been unsubscribed!"
	],
	"activated" => 
	[
		"text" => "Your account has been activated!"
	],
	"cannot_activate" => 
	[
		"text" => "Your account could not be activated, that is the wrong code!",
		"error" => 1
	],
	"cannotunsubscribe" => 
	[
		"text" => "Sorry your details didn't match up to unsubscribe you!",
		"error" => 1
	],
	"cannotunlike" =>
	[
		"text" => "Sorry your details didn't match up to unlike!",
		"error" => 1
	],
	"banned" =>
	[
		"text" => "You were banned, possibly from spam detection or for breaking our rules!",
		"error" => 1
	],
	"spam" => 
	[
		"text" => "You have been sent here to due being flagged as a spammer! Please contact us directly if this is false.",
		"error" => 1
	],
	"unpicked" =>
	[
		"text" => "That article has been removed from the editors pick queue!"
	],
	"picked" =>
	[
		"text" => "That article is now an editors pick!"
	],
	"toomanypicks" =>
	[
		"text" => "Sorry there are already " . core::config('editor_picks_limit') . " articles set as editor picks!"
	]
];
?>
