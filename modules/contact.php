<?php
$templating->set_previous('title', 'Contact Us', 1);
$templating->set_previous('meta_description', 'Contact Us form for ' . $core->config('site_title'), 1);

$templating->merge('contact');
$templating->block('top');

if ($core->config('pretty_urls') == 1)
{
	$submit_link = '/submit-article/';
	if ($user->check_group([1,2,5]))
	{
		$submit_link = $core->config('website_url') . 'admin.php?module=add_article';
	}
	$email_link = '/email-us/';
}
else
{
	$submit_link = $core->config('website_url') . 'index.php?module=articles&view=Submit';
	if ($user->check_group([1,2,5]))
	{
		$submit_link = $core->config('website_url') . 'admin.php?module=add_article';
	}
	$email_link = $core->config('website_url') . 'index.php?module=email_us';
}
$templating->set('submit_link', $submit_link);
$templating->set('email_link', $email_link);
