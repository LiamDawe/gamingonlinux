<?php
$templating->set_previous('title', 'Home' . $templating->get('title', 1)  , 1);
$templating->merge('usercp_modules/usercp_module_home');

include('includes/profile_fields.php');

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'youtube-missing')
	{
		$core->message('That is not a correct Youtube URL format, please use a correct URL like: https://www.youtube.com/gamingonlinux', NULL, 1);
	}
	if ($_GET['message'] == 'twitch-missing')
	{
		$core->message('That is not a correct Twitch URL format, please use a correct URL like: http://www.twitch.tv/gamingonlinux', NULL, 1);
	}
}

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

	$db->sqlquery("SELECT $db_grab_fields `article_bio`, `submission_emails`, `single_article_page`, `per-page`, `articles-per-page`, `twitter_username`, `theme`, `secondary_user_group`, `user_group`, `supporter_link`, `steam_id`, `steam_username`, `google_id`, `google_email`, `forum_type`, `timezone` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($_SESSION['user_id']));

	$usercpcp = $db->fetch();

	$templating->block('top', 'usercp_modules/usercp_module_home');

	if ($user->check_group([1,2,5,6]) == true)
	{
		$templating->block('premium', 'usercp_modules/usercp_module_home');
		$templating->set('url', core::config('website_url'));
		$state = 'disabled';
		if ($user->check_group([1,2,5,6]) == true)
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
			$theme_options .= '<option value="default">default</option>';
		}

		else
		{
			$theme_options .= '<option value="dark">dark</option>';
			$theme_options .= '<option value="default" selected>default</option>';
		}

		$templating->set('theme_options', $theme_options);
	}

	$templating->block('main', 'usercp_modules/usercp_module_home');
	$templating->set('url', core::config('website_url'));
	
	$templating->set('timezone_list', core::timezone_list($usercpcp['timezone']));

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
		$preinput = 0;
		if (isset($field['preinput']) && $field['preinput'] != NULL)
		{
			$preinput = 1;
			$form_input  .= '<div class="input-field"><span class="addon">'.$field['preinput'].'</span>';
		}
		else
		{
			$form_input .= "<div style=\"display:inline;\">";
		}
		$form_input .= "<input id=\"{$field['db_field']}_field\" type=\"text\" name=\"{$field['db_field']}\" value=\"{$usercpcp[$field['db_field']]}\" />";
		$form_input .= "</div>";

		$profile_fields_output .= "<label for=\"{$field['name']}\">$image $span {$field['name']} $form_input <small>$description</small></label><br />";
	}

	$templating->set('profile_fields', $profile_fields_output);

	$submission_emails = '';
	if ($user->check_group([1,2,5]) == true)
	{
		$submission_emails_check = '';
		if ($usercpcp['submission_emails'] == 1)
		{
			$submission_emails_check = 'checked';
		}
		$submission_emails = "Get article submission emails? <input type=\"checkbox\" name=\"submission_emails\" $submission_emails_check /><br />";
	}
	$templating->set('submission_emails', $submission_emails);

	$single_article_yes = '';
	if ($usercpcp['single_article_page'] == 1)
	{
		$single_article_yes = 'selected';
	}
	$templating->set('single_article_yes', $single_article_yes);

	$single_article_no = '';
	if ($usercpcp['single_article_page'] == 0)
	{
		$single_article_no = 'selected';
	}
	$templating->set('single_article_no', $single_article_no);

	$templating->set('bio', $usercpcp['article_bio']);

	$normal_set = '';
	$flat_set = '';
	if ($usercpcp['forum_type'] == 'normal_forum')
	{
		$normal_set = 'selected';
	}
	if ($usercpcp['forum_type'] == 'flat_forum')
	{
		$flat_set = 'selected';
	}

	$forum_types = '<option value="normal_forum" '.$normal_set.'>Category view with forums</option><option value="flat_forum" '.$flat_set.'>A list of all topics</option>';
	$templating->set('forum_types', $forum_types);

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

	$twitter_button = '';
	if (core::config('twitter_login') == 1)
	{
		if (!empty($usercpcp['twitter_username']))
		{
			$twitter_button = '<div class="box"><div class="body group"><form method="post" action="'.core::config('website_url').'usercp.php?module=home">
			Current twitter handle linked: @'.$usercpcp['twitter_username'].'<br />
			<button type="submit">Remove linked Twitter account</button>
			<input type="hidden" name="act" value="twitter_remove" />
			</form></div></div>';
		}

		else
		{
			$twitter_button = '<div class="box"><div class="body group"><a href="'.core::config('website_url').'index.php?module=login&twitter" class="btn-auth btn-twitter"><span class="btn-icon"><img src="'.core::config('website_url'). 'templates/' . core::config('template') .'/images/social/white/twitter.png" /> </span>Sign in with <b>Twitter</b></a></div></div>';
		}
	}
	$templating->set('twitter_button', $twitter_button);

	$steam_button = '';
	if (core::config('steam_login') == 1)
	{
		if (!empty($usercpcp['steam_username']))
		{
			$steam_button = '<div class="box"><div class="body group"><form method="post" action="'.core::config('website_url').'usercp.php?module=home">
			Current Steam user linked: '.$usercpcp['steam_username'].'<br />
			If this username is old it doesn\'t matter!<br />
			<button type="submit" class="btn btn-danger">Remove a linked Steam account</button>
			<input type="hidden" name="act" value="steam_remove" />
			</form></div></div>';
		}

		else
		{
			$steam_button = '<div class="box"><div class="body group"><a href="'.core::config('website_url').'index.php?module=login&steam" class="btn-auth btn-steam"><span class="btn-icon"><img src="'.core::config('website_url'). 'templates/' . core::config('template') .'/images/social/white/steam.png" /> </span>Sign in with <b>Steam</b></a></div></div>';
		}
	}
	$templating->set('steam_button', $steam_button);
	
	$google_button = '';
	if (core::config('google_login') == 1)
	{
		if (!empty($usercpcp['google_id']))
		{
			$google_button = '<div class="box"><div class="body group"><form method="post" action="'.core::config('website_url').'usercp.php?module=home">
			Current Google Email linked: '.$usercpcp['google_email'].'<br />
			<button type="submit" class="btn btn-danger">Remove a linked Google account</button>
			<input type="hidden" name="act" value="google_remove" />
			</form></div></div>';
		}

		else
		{
			$client_id = core::config('google_login_public'); 
			$client_secret = core::config('google_login_secret');
			$redirect_uri = core::config('website_url') . 'includes/google/login.php';
			require_once (core::config('path') . 'includes/google/libraries/Google/autoload.php');
			$client = new Google_Client();
			$client->setClientId($client_id);
			$client->setClientSecret($client_secret);
			$client->setRedirectUri($redirect_uri);
			$client->addScope("email");
			$client->addScope("profile");
			$service = new Google_Service_Oauth2($client);
			$authUrl = $client->createAuthUrl();
				
			$google_button = '<div class="box"><div class="body group"><a href="'.$authUrl.'" class="btn-auth btn-google"><span class="btn-icon"><img src="'.core::config('website_url'). 'templates/' . core::config('template') .'/images/social/white/google-plus.png" /> </span>Link your <b>Google</b> account</a></div></div>';
				
		}
	}
	$templating->set('google_button', $google_button);
}

else if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Update')
	{
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

		$forum_type_sql = $_POST['forum_type'];
		if ($_POST['forum_type'] != 'normal_forum' && $_POST['forum_type'] != 'flat_forum')
		{
			$forum_type_sql = 'normal_forum';
		}

		$single_article_page = 0;
		if ($_POST['single_article_page'] == 1 || $_POST['single_article_page'] == 0)
		{
			$single_article_page = $_POST['single_article_page'];
		}

		$submission_emails = 0;
		if ($user->check_group([1,2,5]) == true)
		{
			if (isset($_POST['submission_emails']))
			{
				$submission_emails = 1;
			}
		}

		$bio = core::make_safe($_POST['bio'], ENT_QUOTES);

		$user_update_sql = "UPDATE ".$core->db_tables['users']." SET `submission_emails` = ?, `single_article_page` = ?, `articles-per-page` = ?, `per-page` = ?, `article_bio` = ?, `forum_type` = ?, `timezone` = ? WHERE `user_id` = ?";
		$user_update_query = $db->sqlquery($user_update_sql, array($submission_emails, $single_article_page, $aper_page, $per_page, $bio, $forum_type_sql, $_POST['timezone'], $_SESSION['user_id']));

		$_SESSION['per-page'] = $per_page;
		$_SESSION['articles-per-page'] = $aper_page;
		
		$db_grab_fields = '';
		foreach ($profile_fields as $field)
		{
			// tell them to do it properly
			if ($field['db_field'] == 'youtube' && (!empty($_POST['youtube']) && strpos($_POST['youtube'], "youtube.com") === false))
			{
				header("Location: " . core::config('website_url') . "usercp.php?module=home&message=youtube-missing");
				die();
			}

			// tell them to do it properly
			if ($field['db_field'] == 'twitch' && (!empty($_POST['twitch']) && strpos($_POST['twitch'], "twitch.tv") === false))
			{
				header("Location: " . core::config('website_url') . "usercp.php?module=home&message=twitch-missing");
				die();
			}

			// make sure the fields can't be just the basic url for broken junk links
			if ($field['db_field'] == 'steam' && ($_POST['steam'] == 'http://steamcommunity.com/id/' || $_POST['steam'] == 'https://steamcommunity.com/id/'))
			{
				$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `{$field['db_field']}` = '' WHERE `user_id` = ?", array($_SESSION['user_id']));
			}
			else if ($field['db_field'] == 'twitch' && ($_POST['twitch'] == 'https://www.twitch.tv/' || $_POST['twitch'] == 'http://www.twitch.tv/'))
			{
				$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `{$field['db_field']}` = '' WHERE `user_id` = ?", array($_SESSION['user_id']));
			}
			else
			{
				$sanatized = htmlspecialchars($_POST[$field['db_field']]);
				$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `{$field['db_field']}` = ? WHERE `user_id` = ?", array($sanatized, $_SESSION['user_id']));
			}
		}

		header("Location: " . $core->config('website_url') . "usercp.php?module=home&updated");
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
		$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `supporter_link` = ?, `theme` = ? WHERE `user_id` = ?", array($supporter_link, $_POST['theme'], $_SESSION['user_id']), 'usercp_module_home.php');

		header("Location: " . core::config('website_url') . "usercp.php?module=home&updated");
	}

	if ($_POST['act'] == 'twitter_remove')
	{
		$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `twitter_username` = ?, `oauth_uid` = ?, `oauth_provider` = ? WHERE `user_id` = ?", array('', '', '', $_SESSION['user_id']));

		header("Location: " . core::config('website_url') . "usercp.php");
	}

	if ($_POST['act'] == 'steam_remove')
	{
		$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `steam_username` = ?, `steam_id` = ? WHERE `user_id` = ?", array('', '', $_SESSION['user_id']));

		header("Location: " . core::config('website_url') . "usercp.php");
	}
	
	if ($_POST['act'] == 'google_remove')
	{
		$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `google_id` = ?, `google_email` = ? WHERE `user_id` = ?", array('', '', $_SESSION['user_id']));

		header("Location: " . core::config('website_url') . "usercp.php");
	}
}
?>
