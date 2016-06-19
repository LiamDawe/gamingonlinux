<?php
$templating->set_previous('title', 'Home' . $templating->get('title', 1)  , 1);
$templating->merge('usercp_modules/usercp_module_home');

include('includes/profile_fields.php');

if (isset($_GET['updated']))
{
	$core->message('You have updated your profile!');
}

if (!isset($_POST['act']))
{
	$db_grab_fields = '';
	foreach ($profile_fields as $field)
	{
		$db_grab_fields .= "{$field['db_field']},";
	}

	$db->sqlquery("SELECT $db_grab_fields `article_bio`, `per-page`, `articles-per-page`, `pc_info_public`, `twitter_username`, `distro`, `auto_subscribe`, `auto_subscribe_email`, `email_on_pm`, `theme`, `secondary_user_group`, `user_group`, `supporter_link`, `steam_id`, `steam_username`, `auto_subscribe_new_article`, `email_options`, `login_emails` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));

	$usercpcp = $db->fetch();

	$templating->block('top', 'usercp_modules/usercp_module_home');

	if ($user->check_group(1,2) == TRUE || $user->check_group(6,7))
	{
		$templating->block('premium', 'usercp_modules/usercp_module_home');
		$templating->set('url', core::config('website_url'));
		$state = 'disabled';
		if ($user->check_group(1,2) == true || $user->check_group(5,6))
		{
			$state = '';
		}
		$templating->set('state', $state);

		$supporter_link = '';
		if ($usercpcp['secondary_user_group'] == 6 && $usercpcp['user_group'] != 1 && $usercpcp['user_group'] != 2)
		{
			$supporter_link = "<br />Donate Page Link <em>Here you may enter a link to sit beside your name on the Support Us</em>:<br />
			<input $state type=\"text\" name=\"supporter_link\" value=\"{$usercpcp['supporter_link']}\" /><br />";
		}

		$templating->set('supporter_link', $supporter_link);

		$theme_options = '';
		if ($usercpcp['theme'] == 'dark')
		{
			$theme_options .= '<option value="dark" selected>dark</option>';
			$theme_options .= '<option value="light">light</option>';
		}

		else
		{
			$theme_options .= '<option value="dark">dark</option>';
			$theme_options .= '<option value="light" selected>light</option>';
		}

		$templating->set('theme_options', $theme_options);
	}

	$templating->block('main', 'usercp_modules/usercp_module_home');
	$templating->set('url', core::config('website_url'));

	$profile_fields_output = '';

	foreach ($profile_fields as $field)
	{
		$url = '';
		if ($field['base_link_required'] == 1)
		{
			$url = $field['base_link'];
		}

		$image = '';
		if ($field['image'] != NULL)
		{
			$image = "<img src=\"{$field['image']}\" alt=\"{$field['name']}\" />";
		}

		$span = '';
		if (isset($field['span']))
		{
			$span = $field['span'];
		}

		$description = '';
		if (isset($field['description']))
		{
			$description = ' - ' . $field['description'];
		}

		$form_input = "";
		if ($field['db_field'] == 'steam')
		{
			$form_input  .= "<div class=\"form-group\"><span class=\"preinput\">http://steamcommunity.com/id/</span>";
		}
		else
		{
			$form_input .= "<div style=\"display:inline;\">";
		}
		$form_input .= "<input id=\"{$field['db_field']}_field\" type=\"text\" name=\"{$field['db_field']}\" class=\"form-control\" value=\"{$usercpcp[$field['db_field']]}\" />";
		$form_input .= "</div>";

		$profile_fields_output .= "<label for=\"{$field['name']}\">$image $span {$field['name']} $form_input <small>$description</small></label><br />";
	}

	$templating->set('profile_fields', $profile_fields_output);

	$templating->set('bio', $usercpcp['article_bio']);

	$subscribe_check = '';
	if ($usercpcp['auto_subscribe'] == 1)
	{
		$subscribe_check = 'checked';
	}

	$subscribe_article_check = '';
	if ($usercpcp['auto_subscribe_new_article'] == 1)
	{
		$subscribe_article_check = 'checked';
	}

	$subscribe_email_check = '';
	if ($usercpcp['auto_subscribe_email'] == 1)
	{
		$subscribe_email_check = 'checked';
	}

	$email_pm = '';
	if ($usercpcp['email_on_pm'] == 1)
	{
		$email_pm = 'checked';
	}

	$email_login = '';
	if ($usercpcp['login_emails'] == 1)
	{
		$email_login = 'checked';
	}

	// sort out user email preferences for getting replies in their inbox
	$all_check = '';
	if ($usercpcp['email_options'] == 1)
	{
		$all_check = 'selected';
	}

	$one_check = '';
	if ($usercpcp['email_options'] == 2)
	{
		$one_check = 'selected';
	}

	$email_options = '<option value=1" '. $all_check .'>All - Get all replies to your email</option><option value=2" ' . $one_check . '>New reply only - Get the first new reply, then none until you visit the article/forum post or reply again.</option>';
	$templating->set('email_options', $email_options);

	$templating->set('subscribe_check', $subscribe_check);
	$templating->set('subscribe_article_check', $subscribe_article_check);
	$templating->set('subscribe_email_check', $subscribe_email_check);
	$templating->set('email_on_pm', $email_pm);
	$templating->set('email_on_login', $email_login);

	$page_options = '';
	$per_page_selected = '';
	for ($i = 10; $i <= 50; $i += 5)
	{
		if ($i == $usercpcp['per-page'])
		{
			$per_page_selected = 'selected';
		}
		$page_options .= '<option value="'.$i.'" '.$per_page_selected.'>'.$i.'</a>';
		$per_page_selected = '';
	}
	$templating->set('per-page', $page_options);

	$apage_options = '';
	$aper_page_selected = '';
	for ($i = 15; $i <= 30; $i += 5)
	{
		if ($i == $usercpcp['articles-per-page'])
		{
			$aper_page_selected = 'selected';
		}
		$apage_options .= '<option value="'.$i.'" '.$aper_page_selected.'>'.$i.'</a>';
		$aper_page_selected = '';
	}
	$templating->set('aper-page', $apage_options);

	if (!empty($usercpcp['twitter_username']))
	{
		$twitter_button = "<div class=\"box\"><div class=\"body group\"><form method=\"post\" action=\"/usercp.php?module=home\">
		Current twitter handle linked: @{$usercpcp['twitter_username']}<br />
		<button type=\"submit\">Remove linked Twitter account</button>
		<input type=\"hidden\" name=\"act\" value=\"twitter_remove\" />
		</form></div></div>";
	}

	else
	{
		$twitter_button = '<div class="box"><div class="body group"><form method="post" action="/index.php?module=login&twitter">
		<button type="submit">Link a Twitter account</button>
		</form></div></div>';
	}

	$templating->set('twitter_button', $twitter_button);

	if (!empty($usercpcp['steam_username']))
	{
		$steam_button = "<div class=\"box\"><div class=\"body group\"><form method=\"post\" action=\"/usercp.php?module=home\">
		Current Steam user linked: {$usercpcp['steam_username']}<br />
		If this username is old it doesn't matter!<br />
		<button type=\"submit\" class=\"btn btn-danger\">Remove a linked Steam account</button>
		<input type=\"hidden\" name=\"act\" value=\"steam_remove\" />
		</form></div></div>";
	}

	else
	{
		$steam_button = '<div class="box"><div class="body group"><form method="post" action="/index.php?module=login&steam">
		<button type="submit" formaction="/index.php?module=login&amp;steam"><img src="'.core::config('website_url').'uploads/steam_login_with_large_border.png" /></button>
		</form></div></div>';
	}

	$templating->set('steam_button', $steam_button);

	$templating->block('pcdeets', 'usercp_modules/usercp_module_home');

	$public_info = '';
	if ($usercpcp['pc_info_public'] == 1)
	{
		$public_info = 'checked';
	}
	$templating->set('public_check', $public_info);

	// grab distros
	$distro_list = '';
	$db->sqlquery("SELECT `name` FROM `distributions` ORDER BY `name` ASC");
	while ($distros = $db->fetch())
	{
			$selected = '';
			if ($usercpcp['distro'] == $distros['name'])
			{
				$selected = 'selected';
			}
			$distro_list .= "<option value=\"{$distros['name']}\" $selected>{$distros['name']}</option>";
	}
	$templating->set('distro_list', $distro_list);

	$db->sqlquery("SELECT `what_bits`, `dual_boot`, `cpu_vendor`, `cpu_model`, `gpu_vendor`, `gpu_model`, `gpu_driver`, `ram_count`, `monitor_count`, `gaming_machine_type`, `resolution` FROM `user_profile_info` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$additional = $db->fetch();

	$arc_32 = '';
	if ($additional['what_bits'] == '32bit')
	{
		$arc_32 = 'selected';
	}
	$arc_64 = '';
	if ($additional['what_bits'] == '64bit')
	{
		$arc_64 = 'selected';
	}
	$what_bits_options = '<option value="32bit" ' . $arc_32 . '>32bit</option><option value="64bit" '.$arc_64.'>64bit</option>';
	$templating->set('what_bits_options', $what_bits_options);



	$windows = '';
	$mac = '';
	$nope = '';
	if ($additional['dual_boot'] == 'Yes Windows')
	{
		$windows = 'selected';
	}
	if ($additional['dual_boot'] == 'Yes Mac')
	{
		$mac = 'selected';
	}
	if ($additional['dual_boot'] == 'No')
	{
		$nope = 'selected';
	}

	$dual_boot_options = '<option value="Yes Windows" '.$windows.'>Yes With Windows</option><option value="Yes Mac" '.$mac.'>Yes With Mac</option><option value="No" '.$nope.'>No</option>';
	$templating->set('dual_boot_options', $dual_boot_options);

	$intel = '';
	if ($additional['cpu_vendor'] == 'Intel')
	{
		$intel = 'selected';
	}
	$amd = '';
	if ($additional['cpu_vendor'] == 'AMD')
	{
		$amd = 'selected';
	}
	$cpu_options = '<option value="AMD" '.$amd.'>AMD</option><option value="Intel" '.$intel.'>Intel</option>';
	$templating->set('cpu_options', $cpu_options);

	$templating->set('cpu_model', $additional['cpu_model']);

	$intel_gpu = '';
	if ($additional['gpu_vendor'] == 'Intel')
	{
		$intel_gpu = 'selected';
	}
	$amd_gpu = '';
	if ($additional['gpu_vendor'] == 'AMD')
	{
		$amd_gpu = 'selected';
	}
	$nvidia_gpu = '';
	if ($additional['gpu_vendor'] == 'Nvidia')
	{
		$nvidia_gpu = 'selected';
	}
	$gpu_options = '<option value="AMD" '.$amd_gpu.'>AMD</option><option value="Intel" '.$intel_gpu.'>Intel</option><option value="Nvidia" '.$nvidia_gpu.'>Nvidia</option>';
	$templating->set('gpu_options', $gpu_options);

	$templating->set('gpu_model', $additional['gpu_model']);

	$open = '';
	if ($additional['gpu_driver'] == 'Open Source')
	{
		$open = 'selected';
	}
	$prop = '';
	if ($additional['gpu_driver'] == 'Proprietary')
	{
		$prop = 'selected';
	}
	$hybrid = '';
	if ($additional['gpu_driver'] == 'Hybrid Driver')
	{
		$hybrid = 'selected';
	}
	$gpu_driver = '<option value="Open Source" '.$open.'>Open Source</option><option value="Proprietary" '.$prop.'>Proprietary</option>';
	$templating->set('gpu_driver', $gpu_driver);

	// RAM
	$ram_options = '';
	$ram_selected = '';
	for ($i = 1; $i <= 64; $i++)
	{
		if ($i == $additional['ram_count'])
		{
			$ram_selected = 'selected';
		}
    $ram_options .= '<option value="'.$i.'" '.$ram_selected.'>'.$i.'GB</a>';
		$ram_selected = '';
	}
	$templating->set('ram_options', $ram_options);

	// Monitors
	$monitor_options = '';
	$monitor_selected = '';
	for ($i = 1; $i <= 5; $i++)
	{
		if ($i == $additional['monitor_count'])
		{
			$monitor_selected = 'selected';
		}
		$monitor_options .= '<option value="'.$i.'" '.$monitor_selected.'>'.$i.'</a>';
		$monitor_selected = '';
	}
	$templating->set('monitor_options', $monitor_options);

	// Resolution
	$resolution_options = '';
	$resolution_selected = '';
	$resolution_options = array(
		"800x600",
		"1024x600",
		"1024x768",
		"1152x864",
		"1280x720",
		"1280x768",
		"1280x800",
		"1280x1024",
		"1360x768",
		"1366x768",
		"1440x900",
		"1400x1050",
		"1600x900",
		"1600x1200",
		"1680x1050",
		"1920x1080",
		"1920x1200",
		"2560x1080",
		"2560x1440",
		"2560x1600",
		"3440x1440",
		"3840x2160");
	foreach ($resolution_options as $res)
	{
		if ($res == $additional['resolution'])
		{
			$resolution_selected = 'selected';
		}
		$resolution_options .= '<option value="'.$res.'" '.$resolution_selected.'>'.$res.'</a>';
		$resolution_selected = '';
	}
	$templating->set('resolution_options', $resolution_options);

	// Type of machine
	$desktop = '';
	if ($additional['gaming_machine_type'] == 'Desktop')
	{
		$desktop = 'selected';
	}

	$laptop = '';
	if ($additional['gaming_machine_type'] == 'Laptop')
	{
		$laptop = 'selected';
	}

	$sofa = '';
	if ($additional['gaming_machine_type'] == 'Sofa/Console PC')
	{
		$sofa = 'selected';
	}

	$machine_options = '<option value="Desktop" '.$desktop.'>Desktop</option><option value="Laptop" '.$laptop.'>Laptop</option><option value="Sofa/Console PC" '.$sofa.'>Sofa/Console PC</option>';
	$templating->set('machine_options', $machine_options);
}

else if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Update')
	{
		$subscribe = 0;
		$subscribe_article = 0;
		$subscribe_emails = 0;
		$email_on_pm = 0;
		$email_on_login = 0;
		$hide_developer_status = 0;
		$public = 0;

		if (isset($_POST['subscribe']))
		{
			$subscribe = 1;
		}

		if (isset($_POST['subscribe_article']))
		{
			$subscribe_article = 1;
		}

		if (isset($_POST['emails']))
		{
			$subscribe_emails = 1;
		}

		if (isset($_POST['emailpm']))
		{
			$email_on_pm = 1;
		}

		if (isset($_POST['emaillogin']))
		{
			$email_on_login = 1;
		}

		if (isset($_POST['public']))
		{
			$public = 1;
		}

		$per_page = 10;
		if (is_numeric($_POST['per-page']))
		{
			$per_page = $_POST['per-page'];
		}

		$aper_page = 15;
		if (is_numeric($_POST['articles-per-page']))
		{
			$aper_page = $_POST['articles-per-page'];
		}

		// no nasty html grr
		$bio = htmlspecialchars($_POST['bio'], ENT_QUOTES);

		$db->sqlquery("UPDATE `users` SET `articles-per-page` = ?, `per-page` = ?, `auto_subscribe` = ?, `auto_subscribe_email` = ?, `article_bio` = ?, `email_on_pm` = ?, `auto_subscribe_new_article` = ?, `email_options` = ?, `login_emails` = ?, `distro` = ?, `pc_info_public` = ? WHERE `user_id` = ?", array($aper_page, $per_page, $subscribe, $subscribe_emails, $bio, $email_on_pm, $subscribe_article, $_POST['email_options'], $email_on_login, $_POST['distribution'], $public, $_SESSION['user_id']));

		$_SESSION['per-page'] = $per_page;
		$_SESSION['articles-per-page'] = $aper_page;

		// additional profile fields
		$sql_additional = "UPDATE `user_profile_info` SET
		`what_bits` = ?,
		`dual_boot` = ?,
		`cpu_vendor` = ?,
		`cpu_model` = ?,
		`gpu_vendor` = ?,
		`gpu_model` = ?,
		`gpu_driver` = ?,
		`ram_count` = ?,
		`monitor_count` = ?,
		`resolution` = ?,
		`gaming_machine_type` = ?,
		`date_updated` = ?
		WHERE
		`user_id` = ?";
		$db->sqlquery($sql_additional, array(
		$_POST['what_bits'],
		$_POST['dual_boot'],
		$_POST['cpu_vendor'],
		$_POST['cpu_model'],
		$_POST['gpu_vendor'],
		$_POST['gpu_model'],
		$_POST['gpu_driver'],
		$_POST['ram_count'],
		$_POST['monitor_count'],
		$_POST['resolution'],
		$_POST['gaming_machine_type'],
		gmdate("Y-n-d H:i:s"),
		$_SESSION['user_id']));

		$db_grab_fields = '';
		foreach ($profile_fields as $field)
		{
			// make sure the Steam field can't be a plain steam profile url for broken links
			if ($field['db_field'] == 'steam' && $_POST['steam'] == 'http://steamcommunity.com/id/')
			{
				$db->sqlquery("UPDATE `users` SET `{$field['db_field']}` = '' WHERE `user_id` = ?", array($_SESSION['user_id']));
			}
			else
			{
				$sanatized = htmlspecialchars($_POST[$field['db_field']]);
				$db->sqlquery("UPDATE `users` SET `{$field['db_field']}` = ? WHERE `user_id` = ?", array($sanatized, $_SESSION['user_id']));
			}
		}

		header("Location: " . core::config('website_url') . "usercp.php?module=home&updated");
	}

	// need to add in a check in here to doubly be sure they are a premium person
	if ($_POST['act'] == 'premium')
	{
		$supporter_link = '';
		// if they have a supporter link set
		if (isset($_POST['supporter_link']))
		{
			$supporter_link = $_POST['supporter_link'];
		}

		// need to add theme updating back into here
		$db->sqlquery("UPDATE `users` SET `supporter_link` = ?, `theme` = ? WHERE `user_id` = ?", array($supporter_link, $_POST['theme'], $_SESSION['user_id']), 'usercp_module_home.php');

		$_SESSION['theme'] = $_POST['theme'];

		header("Location: " . core::config('website_url') . "usercp.php?module=home&updated");
	}

	if ($_POST['act'] == 'twitter_remove')
	{
		$db->sqlquery("UPDATE `users` SET `twitter_username` = ?, `oauth_uid` = ?, `oauth_provider` = ? WHERE `user_id` = ?", array('', '', '', $_SESSION['user_id']));

		header("Location: " . core::config('website_url') . "usercp.php");
	}

	if ($_POST['act'] == 'steam_remove')
	{
		$db->sqlquery("UPDATE `users` SET `steam_username` = ?, `steam_id` = ? WHERE `user_id` = ?", array('', '', $_SESSION['user_id']));

		header("Location: " . core::config('website_url') . "usercp.php");
	}
}
?>
