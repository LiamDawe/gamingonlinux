<?php
plugins::register_hook('into_post_username', 'distro_in_username');

function hook_distro_in_username($database, $core, $user_info)
{
	$into_username = '';
	if (!empty($user_info['distro']) && $user_info['distro'] != 'Not Listed')
	{
		$into_username .= '<img title="' . $user_info['distro'] . '" class="distro tooltip-top"  alt="" src="' . $core->config('website_url') . 'templates/'.$core->config('template').'/images/distros/' . $user_info['distro'] . '.svg" />';
	}
	return $into_username;
}
