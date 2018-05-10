<?php
$templating->set_previous('meta_description', 'Cookie Preferences', 1);
$templating->set_previous('title', 'Cookie Preferences', 1);

$templating->load('cookie_prefs');
$templating->block('main', 'cookie_prefs');

$youtube_checked = '';
if (isset($_COOKIE['gol_youtube_consent']))
{
	if ($_COOKIE['gol_youtube_consent'] == 'yup')
	{
		$youtube_checked = 'checked';
	}
}

$templating->set('youtube_cookies_check', $youtube_checked);
?>
