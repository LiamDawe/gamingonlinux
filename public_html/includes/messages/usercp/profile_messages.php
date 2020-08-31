<?php
return [
	"url-characters" => 
	[
		"text"	=> 'Your profile URL had incorrect characters. We only allow letters, numbers, underscores and dashes.',
		"error" => 1
    ],
    "exists" =>
    [
        "text" => 'Sorry, that profile URL already exists. It needs to be unique.',
        "error" => 1
    ],
    "naughty" =>
    [
        "text" => 'Sorry, your profile url had terms in it we do not allow. Words like admin and moderator are banned.',
        "error" => 1
    ]
];
?>
