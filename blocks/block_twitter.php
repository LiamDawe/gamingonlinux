<?php
$templating->merge('blocks/block_twitter');
$templating->block('menu');
$theme = $user->get('theme', $_SESSION['user_id']);
if (isset($theme['theme']) && $theme['theme'] == 'default' || !isset($theme['theme']))
{
	$embed = "<a class=\"twitter-timeline\" data-height=\"500\" href=\"https://twitter.com/".core::config('twitter_username')."\">Tweets by ".core::config('twitter_username')."</a> <script async src=\"//platform.twitter.com/widgets.js\" charset=\"utf-8\"></script>";
}

else
{
	$embed = "<a class=\"twitter-timeline\" data-height=\"500\" data-theme=\"dark\" href=\"https://twitter.com/".core::config('twitter_username')."\">Tweets by ".core::config('twitter_username')."</a> <script async src=\"//platform.twitter.com/widgets.js\" charset=\"utf-8\"></script>";
}

$templating->set('embed', $embed);
