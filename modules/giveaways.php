<?php
$templating->set_previous('title', 'Game giveaways', 1);
$templating->set_previous('meta_description', 'GamingOnLinux game giveaways', 1);

$templating->load('giveaways');

if (isset($_GET['id']))
{
	$text = "[giveaway]".$_GET['id']."[/giveaway]";

	$text = $bbcode->replace_giveaways($text, $_GET['id'], 1);

	$templating->block('top');
	$templating->set('text', $text);
}
else
{
	$core->message("You need to provide a giveaway ID.");
}