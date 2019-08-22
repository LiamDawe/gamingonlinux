<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Contact Us', 1);
$templating->set_previous('meta_description', 'Contact Us form for GamingOnLinux', 1);

$templating->load('contact');
$templating->block('top');

$submit_link = '/submit-article/';
if ($user->check_group([1,2,5]))
{
	$submit_link = $core->config('website_url') . 'admin.php?module=add_article';
}

$templating->set('submit_link', $submit_link);
