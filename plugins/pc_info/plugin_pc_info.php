<?php
plugins::register_hook('into_post_user_info', 'pc_info_link');

function hook_pc_info_link($database, $core, $user_info)
{
	$pc_info = '';
	if (isset($user_info['pc_info_public']) && $user_info['pc_info_public'] == 1)
	{
		if ($user_info['pc_info_filled'] == 1)
		{
			$pc_info = '<a class="computer_deets fancybox.ajax" data-fancybox-type="ajax" href="'.$core->config('website_url').'includes/ajax/call_profile.php?user_id='.$user_info['author_id'].'">View PC info</a>';
		}
	}
	
	return $pc_info;
}
