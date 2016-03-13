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

	$db->sqlquery("SELECT $db_grab_fields `article_bio`, `twitter_username`, `auto_subscribe`, `auto_subscribe_email`, `email_on_pm`, `theme`, `secondary_user_group`, `user_group`, `supporter_link`, `steam_id`, `steam_username`, `auto_subscribe_new_article`, `email_options`, `login_emails` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));

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
		$templating->set('gol_premium', $premium);
		$templating->set('state', $state);

		$supporter_link = '';
		if ($usercpcp['secondary_user_group'] == 6 && $usercpcp['user_group'] != 1 && $usercpcp['user_group'] != 2)
		{
			$supporter_link = "<br />Donate Page Link <em>Here you may sit a link to sit beside your name on the donate page</em>:<br />
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

		if (isset($field['dropdown']) && $field['dropdown'] == 1)
		{
			$form_input = "<select class=\"form-control\" name=\"{$field['db_field']}\">";
			foreach($field['option'] as $value)
			{
				$selected = '';
				if ($usercpcp[$field['db_field']] == $value)
				{
					$selected = 'selected';
				}
				$form_input .= "<option value=\"{$value}\" $selected>{$value}</option>";
			}
			$form_input .= '</select>';
		}
		else
		{
			// $steam_url = '';
			// if ($field['db_field'] == 'steam' && empty($usercpcp[$field['db_field']]))
			// {
			// 	$steam_url = 'http://steamcommunity.com/id/';
			// }
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
		}

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

	if (!empty($usercpcp['twitter_username']))
	{
		$twitter_button = "<div class=\"box\"><div class=\"body group\"><form method=\"post\" action=\"/usercp.php?module=home\">
		Current twitter handle linked: @{$usercpcp['twitter_username']}<br />
		<button type=\"submit\">Removed Linked twitter account</button>
		<input type=\"hidden\" name=\"act\" value=\"twitter_remove\" />
		</form></div></div>";
	}

	else
	{
		$twitter_button = '<div class="box"><div class="body group"><form method="post" action="/index.php?module=login&twitter">
		<button type="submit">Link twitter account</button>
		</form></div></div>';
	}

	$templating->set('twitter_button', $twitter_button);

	if (!empty($usercpcp['steam_username']))
	{
		$steam_button = "<div class=\"box\"><div class=\"body group\"><form method=\"post\" action=\"/usercp.php?module=home\">
		Current Steam user linked: {$usercpcp['steam_username']}<br />
		If this username is old it doesn't matter!<br />
		<button type=\"submit\" class=\"btn btn-danger\">Removed Linked Steam account</button>
		<input type=\"hidden\" name=\"act\" value=\"steam_remove\" />
		</form></div></div>";
	}

	else
	{
		$steam_button = '<div class="box"><div class="body group"><form method="post" action="/index.php?module=login&steam">
		<button type="submit" formaction="/index.php?module=login&amp;steam"><img src="'.$config['path'].'uploads/steam_login_with_large_border.png" /></button>
		</form></div></div>';
	}

	$templating->set('steam_button', $steam_button);
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

		// no nasty html grr
		$bio = htmlspecialchars($_POST['bio'], ENT_QUOTES);

		// need to add theme updating back into here
		$db->sqlquery("UPDATE `users` SET `auto_subscribe` = ?, `auto_subscribe_email` = ?, `article_bio` = ?, `email_on_pm` = ?, `auto_subscribe_new_article` = ?, `email_options` = ?, `login_emails` = ? WHERE `user_id` = ?", array($subscribe, $subscribe_emails, $bio, $email_on_pm, $subscribe_article, $_POST['email_options'], $email_on_login, $_SESSION['user_id']), 'usercp_module_home.php');

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
