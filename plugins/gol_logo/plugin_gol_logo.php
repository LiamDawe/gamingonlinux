<?php
plugins::register_hook('icon_hook', 'icon_changer');

function hook_icon_changer($database, $core)
{
	$branding = NULL;
	// april fools, because why not
	if (date('dm') == '0104' && date('H') < 14)
	{
		$branding['icon'] = 'windows_logo.png';
		$branding['title'] = 'Gaming On Windows 10';
	}
	// christmas
	if (date('m') == '12')
	{
		$branding['icon'] = 'icon_xmas.png';
	}
	return $branding;
}
