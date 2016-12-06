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

	$db->sqlquery("SELECT $db_grab_fields `article_bio`, `submission_emails`, `single_article_page`, `per-page`, `articles-per-page`, `twitter_username`, `theme`, `secondary_user_group`, `user_group`, `supporter_link`, `steam_id`, `steam_username`, `forum_type` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));

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
		else if ($field['db_field'] == 'twitter_on_profile')
		{
			$form_input  .= "<div class=\"form-group\"><span class=\"preinput\">https://twitter.com/</span>";
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

	$submission_emails = '';
	if ($user->check_group(1,2) == true || $user->check_group(5) == true)
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
		if ($user->check_group(1,2) == true || $user->check_group(5) == true)
		{
			if (isset($_POST['submission_emails']))
			{
				$submission_emails = 1;
			}
		}

		// no nasty html grr
		$bio = htmlspecialchars($_POST['bio'], ENT_QUOTES);

		$user_update_sql = "UPDATE `users` SET `submission_emails` = ?, `single_article_page` = ?, `articles-per-page` = ?, `per-page` = ?, `article_bio` = ?, `forum_type` = ? WHERE `user_id` = ?";
		$user_update_query = $db->sqlquery($user_update_sql, array($submission_emails, $single_article_page, $aper_page, $per_page, $bio, $forum_type_sql, $_SESSION['user_id']));

		$_SESSION['per-page'] = $per_page;
		$_SESSION['articles-per-page'] = $aper_page;
		$_SESSION['forum_type'] = $forum_type_sql;
		$_SESSION['single_article_page'] = $single_article_page;

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
