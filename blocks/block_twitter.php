<?php
$templating->merge('blocks/block_twitter');
$templating->block('menu');

if (isset($_SESSION['theme']) && $_SESSION['theme'] == 'default' || !isset($_SESSION['theme']))
{
	$embed = "<a class=\"twitter-timeline\" href=\"https://twitter.com/".core::config('twitter_username')."\">Tweets by ".core::config('twitter_username')."</a> <script async src=\"//platform.twitter.com/widgets.js\" charset=\"utf-8\"></script>";
}

else
{
	$embed = "<a class=\"twitter-timeline\" data-theme=\"dark\" href=\"https://twitter.com/".core::config('twitter_username')."\">Tweets by ".core::config('twitter_username')."</a> <script async src=\"//platform.twitter.com/widgets.js\" charset=\"utf-8\"></script>";
}

$templating->set('embed', $embed);
