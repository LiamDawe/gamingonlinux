<?php
$templating->set_previous('title', 'Contact Us', 1);
$templating->set_previous('meta_description', 'Contact Us form for GamingOnLinux.com', 1);

$templating->merge('contact');
$templating->block('top');

if (core::config('pretty_urls') == 1)
{
	$submit_link = '/submit-article/';
	if (isset($_SESSION['user_group']) && $_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5)
	{
		$submit_link = core::config('website_url') . 'admin.php?module=articles&amp;view=add';
	}
	$email_link = '/email-us/';
}
else
{
	$submit_link = core::config('website_url') . 'index.php?module=articles&view=Submit';
	if (isset($_SESSION['user_group']) && $_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5)
	{
		$submit_link = core::config('website_url') . 'admin.php?module=articles&amp;view=add';
	}
	$email_link = core::config('website_url') . 'index.php?module=email_us';
}
$templating->set('submit_link', $submit_link);
$templating->set('email_link', $email_link);
