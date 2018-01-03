<?php
return [
	"link_needed" => 
	[
		"text" => 'You need to put in at least one link!',
		"error" => 1
	],
	"game_add_exists" =>
	[
		"text" => 'That game is already in the database! <a href="/admin.php?module=games&view=edit&id=%d">Click here to edit.</a>',
		"additions" => 1,
		"error" => 2
	],
	"tags_denied" =>
	[
		"text" => "Those tag suggestions have now been removed!"
	],
	"tags_approved" =>
	[
		"text" => "Those tags have now been approved!"
	],
	"submit_approved" => 
	[
		"text" => "You have approved that item for inclusion in the database!"
	],
	"dev_approve_exists" =>
	[
		"text" => "It seems that developer/publisher is already approved in the database! Someone may have gotten there first, or if you edited the name as it was wrong, the correct spelling may exist already (in that case delete it, but be sure first).",
		"error" => 1
	],
	"dev_denied" =>
	[
		"text" => "You have denied that developer/publisher from being included in the database!"
	],
	"dev_doesnt_exist" =>
	[
		"text" => "Sorry, couldn't find that item. Someone must have gotten there first!",
		"error" => 1
	],
	"no_item_type" =>
	[
		"text" => "You didn't select the type of item you're submitting!",
		"error" => 1
	]
];
?>
